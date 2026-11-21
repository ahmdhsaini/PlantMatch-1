# PlantMatch — Project Guide for AI Assistants

> Read this file first. It describes what the project is, how every part fits together,
> the database schema (inferred from code), the data/ML pipeline, the recommendation
> logic, and the known issues. Everything here was derived from reading the source files.

---

## 1. What this project is

**PlantMatch** is a Final Year Project (FYP) web application that recommends plants to
users based on their living environment and care preferences (house type, light, water,
space, skill level, humidity, growth rate, plant category, mature height).

It has three major parts:

| Part | Tech | Files |
|---|---|---|
| Web app (public + logged-in user area) | PHP (procedural, mysqli/PDO), Bootstrap 5, Bootstrap Icons | `*.php`, `css/styles.css`, `js/scripts.js`, `assets/` |
| Data pipeline (PDF → cleaned → encoded CSV) | Python, pdfplumber, pandas, scikit-learn | `convert_plants.py`, `data_preprocessing.ipynb`, `*.csv` |
| Recommendation model training / evaluation | Python, pandas, scikit-learn (cosine similarity, KNN), SQLAlchemy | `Traindata.ipynb`, `hybrid.ipynb`, `collaborative_filtering.ipynb`, `recommend.py`, `plantmatch_hybrid_model.pkl`, `*.png` |
| Chatbot "FloraBot" | PHP + Groq API (OpenAI-compatible), model `llama-3.3-70b-versatile` | `chatbot_backend.php`, `chatbot_ui.php` |

- Domain context: dataset comes from a Singapore flora PDF (`488 page dataset.pdf`, column
  "Lifespan (in Singapore)"); house types are Malaysian-style (High-rise, Landed
  Terrace/Semi-D/Bungalow, "Tradisional" village house). Some code comments/prints are in Malay
  (e.g. "SPLIT DATA UNTUK TRAINING AND TESTING").
- Developer name appears in `process_search.php` header comment.
- No build step, no package manager files, no tests, no `.sql` schema dump in the repo.

---

## 2. Runtime environment

- Local stack: **Laragon** (Windows) — Apache/PHP + MySQL.
- DB connection (`db.php`): host `127.0.0.1`, user `root`, empty password, database
  **`plantmatch_db`**. Several files open their *own* connection instead of including
  `db.php`: `search.php`, `process_search.php`, `chatbot_backend.php` (PDO), and the notebooks
  (`mysql+mysqlconnector://root:@localhost/plantmatch_db`).
- Python 3.14 (Windows) and Anaconda are both referenced in notebook outputs.
  Python libs used: `pdfplumber`, `pandas`, `numpy`, `scikit-learn`, `scipy`, `sqlalchemy`,
  `mysql-connector-python`, `matplotlib`, `openpyxl`.
- Front-end libraries come from CDN (Bootstrap 5.2.3 bundle JS, Bootstrap Icons 1.5–1.11).
  `css/styles.css` is the compiled Start Bootstrap theme (~10.8k lines, includes Bootstrap).
  `js/scripts.js` is a 6-line stub.

To run: put the folder in Laragon's `www`, start Apache + MySQL, create `plantmatch_db`,
import the plant table (see §4) and create the user tables (see §5), open `index.php`.

---

## 3. File map

### 3.1 Shared layout
| File | Purpose |
|---|---|
| `public_header.php` | `<head>` + navbar for guests (Home, Search, About Us, Login). Opens `<body><main>`. |
| `private_header.php` | Calls `session_start()`; **redirects to `login.php` if `$_SESSION['username']` is not set**. Navbar for logged-in users (Dashboard, Search Plants, Get Recommendations, Logout), greets user by name. |
| `footer.php` | Footer, includes `chatbot_ui.php`, loads Bootstrap JS + `js/scripts.js`, closes the HTML. |
| `db.php` | mysqli connection `$conn`. |

### 3.2 Public pages (use `public_header.php`)
| File | Purpose |
|---|---|
| `index.php` | Landing page (marketing content about the system). |
| `about.php` | About page. |
| `faq.php`, `pricing.php`, `contact.php` | Static template pages (contact form is a Start Bootstrap demo form, not wired to a backend). |
| `search.php` | Public plant search by keyword (`?search=`) over `Common_Name` / `Scientific_Name`, paginated 12 per page; decodes encoded columns into labels. |
| `login.php` → `process_login.php` | Login form → verifies with `password_verify`, sets `$_SESSION['user_id']` (= `users.userID`) and `$_SESSION['username']` (= `users.name`), redirects to `user_dashboard.php`. |
| `register.php` → `process_register.php` | Registration (name, email, password, confirm) → `password_hash`, inserts into `users`. |
| `process_search.php` | Redirects to `search.php?keyword=...` (see bug list — `search.php` reads `search`, not `keyword`). |
| `logout.php` | `session_destroy()` → `login.php`. |
| `blog-home.php`, `blog-post.php`, `portfolio-item.php`, `portfolio-overview.php` | Leftover Start Bootstrap template pages. They `include('header.php')`, **which does not exist** → these pages are broken/unused. |

### 3.3 Logged-in user pages (use `private_header.php`)
| File | Purpose |
|---|---|
| `user_dashboard.php` | Profile summary (from `preference` + `users.email`), favorites count, **top-5 recommendations computed live in PHP** (same algorithm as §6.1, without house bonus), latest 4 favorites, latest 5 feedback entries; `?action=delete&plantid=` deletes the user's feedback for that plant. |
| `user_search.php` | Logged-in explorer with keyword + sidebar filters (`cat[]`, `water[]`, `growth[]`, `house[]`, `light[]`) translated into SQL against the encoded columns; 12 per page. |
| `user_plant_details.php?id=` | Plant detail page. Handles POST actions inline (PRG pattern + `$_SESSION['flash_msg']`): `favorite`, `unfavorite`, feedback `insert`, `update`, `delete`. Shows all users' feedback. |
| `user_recommendation_form.php` | 9-step "Plant Match Quiz"; pre-filled from the user's `preference` row. Posts to `user_process_recommendation_form.php`. |
| `user_process_recommendation_form.php` | **Active quiz handler.** Upserts `preference` (prepared statement), runs `python3 recommend.py <userID> '<json>'` via `shell_exec`, stores the result in `$_SESSION['recommended_plant_ids']`, redirects to `user_view_recommendations.php`. |
| `user_process_recommendation_form1.php` | **Older/alternate handler (not linked from any form).** Pure-PHP weighted cosine + Euclidean hybrid; writes top 10 into `recommendation` table. Expects *text* category values in the plant table (see §4.3 mismatch). |
| `user_view_recommendations.php` | Shows **Top 10** recommendations. **Recomputes scores itself in PHP** from the `preference` row (§6.1) — it does *not* read `$_SESSION['recommended_plant_ids']`. Falls back to 10 random plants with fake 70–85% scores if no preference exists. AJAX `?ajax_action=favorite|unfavorite&id=` toggles favorites. |
| `user_favorites.php` | Lists favorites (`favorite` JOIN plants), AJAX unfavorite (`?action=unfavorite&id=`), checkboxes to compare. |
| `user_compare.php?ids=1,2,3` | Side-by-side comparison, max 5 plants. |
| `user_saved_garden.php` | Lists plants in the `save` table, AJAX delete (`?action=delete&id=`). |
| `user_save_plant.php?id=` | Adds to `save` table. |
| `user_add_favorite.php?id=` | Adds to `favorite` table (older endpoint). |
| `user_submit_feedback.php` (POST) | Inserts feedback (older endpoint; details page now handles feedback inline). |
| `user_feedbacklist.php` | Lists the user's feedback, delete via `?action=delete&plantid=`. |
| `user_update_profile.php` | Update name/email and optionally password; syncs `$_SESSION['username']`. |

`user_add_favorite.php`, `user_save_plant.php`, `user_submit_feedback.php` find the user ID by
scanning **every row of `users`** and matching any column to the session username
(case-insensitive) — legacy approach; newer pages use `$_SESSION['user_id']` directly.

### 3.4 Chatbot
- `chatbot_ui.php` — floating green launcher bubble + popup window (included by `footer.php`,
  so it appears on every page). Sends `fetch('chatbot_backend.php', {message})`.
- `chatbot_backend.php` — no login required. Keeps last 14 messages in
  `$_SESSION['chatbot_history']`. Loads the **first 30 plants** (`LIMIT 30`) from the plant
  table, converts numeric features to words, builds a system prompt for "FloraBot", calls
  `https://api.groq.com/openai/v1/chat/completions` (model `llama-3.3-70b-versatile`,
  temperature 0.5, max_tokens 300). Returns `{reply}` or `{error}`.
  The API key `GROK_API_KEY` is read from `config.php` (git-ignored; template `config.example.php`).

### 3.5 Python / data files
| File | Purpose |
|---|---|
| `488 page dataset.pdf` | Source flora dataset (tables). |
| `convert_plants.py` | Standalone version of notebook step 1: PDF tables → `flora_extracted_improved.csv`. |
| `data_preprocessing.ipynb` | Full cleaning + feature engineering + encoding pipeline (§4). |
| `collaborative_filtering.ipynb` | Early user-user KNN collaborative filtering experiment on the live `feedback` table. |
| `hybrid.ipynb` | Imports the encoded CSV into MySQL; hybrid CBF+CF evaluation (Precision/Recall@K over K/alpha configs); saves `final_confusion_matrix.png`, `hybrid_accuracy_recall_metrics_curve.png`. |
| ~~`Traindata.ipynb`~~ | Removed (its metrics were hard-coded, see §7). It had produced `plantmatch_hybrid_model.pkl`, which is kept. |
| `recommend.py` | CLI called by PHP: loads the pickle and prints a JSON list of 10 plant IDs (§6.3). |
| `plantmatch_hybrid_model.pkl` | Dict: `content_similarity_df` (3170×3170 cosine matrix indexed by plant ID), `utility_matrix_train` (users × plantID ratings), `optimized_alpha` (0.12), `model_metrics`. |
| CSVs | See §4. The large ones (~53 MB) contain base64 images. |

---

## 4. Data pipeline (`data_preprocessing.ipynb`)

```
488 page dataset.pdf
  └─(pdfplumber extract_table, line strategy)→ flora_extracted_improved.csv   (3,659 rows, raw, repeated headers)
      └─(drop repeated header rows, set clean headers)→ flora_final_cleaned.csv  (3,170 plants)
          └─(strip botanical author citations from Scientific Name)→ flora_final_cleaned1.csv
              └─(normalize commas in every column)→ flora_final_cleaned2.csv
                  └─(rule-based feature heuristics)→ flora_topic_focused.csv
                      └─(+ ID, Image_URL, edited values — done outside the notebook)→ PlantMatch_Ultimate_Complete - Copy.csv
                          └─(ordinal map + MinMax + one-hot)→ final_encoded_plant_features.csv  → imported as MySQL table
```
`filled_flora.csv` (36,896 lines, multi-line headers) is another intermediate/raw export.

### 4.1 Raw columns (cleaned CSVs)
`Number, Scientific Name, Common Names, Plant Division, Plant Growth Form,
Lifespan (in Singapore), Mode of Nutrition, Plant Shape, Maximum Height From, Maximum Height To`

### 4.2 Heuristic feature engineering (`flora_topic_focused.csv`)
All features except height are derived **from the category only** (rule-based, not measured):

- **Mature_Height_cm**: first number in "Maximum Height To"; ×100 if it contains "m" or is < 100; default 100.
- **Categories** (9 classes, from growth form/division, in priority order): Ferns → Palms →
  Climbers & Vines → Trees → Shrubs → Succulents & Cacti → Edibles & Herbs (herb/vegetable/
  edible/medicinal) → Flowering Ornamentals (flower/orchid) → else Foliage Plants.
- **Space_Required**: height < 50 → Small; ≤ 200 → Medium; else Large.
- **House_Type**: Trees or Large Shrubs → Landed (Terrace/Semi-D/Bungalow); Climbers & Vines →
  Tradisional/Spacious (Village House); else High-rise (Apartment/Flat).
- **Gardening_Level**: Trees/Shrubs/Palms → Beginner; Foliage/Climbers/Succulents → Moderate;
  Flowering/Edibles/Ferns → Expert.
- **Light_Condition**: Trees/Succulents/Palms → Full Sun; Shrubs/Climbers/Edibles → Partial Sun;
  Ferns/Foliage/Flowering → Low Light.
- **Water_Frequency**: Ferns/Edibles/Flowering → Daily; Succulents → Monthly; else Weekly.
- **Growth_Rate**: Climbers/Edibles → Fast; Shrubs/Flowering/Foliage/Ferns → Moderate; else Slow.
- **Humidity_Need**: Ferns/Foliage → High; Succulents → Low; else Medium.

Note: `PlantMatch_Ultimate_Complete - Copy.csv` has values that differ from these rules for some
rows (e.g. row 1 is Landed/Moderate/Full Sun/Flowering) and its `Mature_Height_cm` is already
normalized (0–1), so it was edited/regenerated outside the notebook.

### 4.3 Encoding (`final_encoded_plant_features.csv`) — **this is what the DB table holds**
1. Ordinal maps: Gardening_Level (Beginner 1, Moderate 2, Expert 3), Space_Required (Small 1,
   Medium 2, Large 3), Light_Condition (Low Light 1, Partial Sun/Partial Shade 2, Full Sun 3),
   Water_Frequency (Monthly 1, Weekly 2, Daily 3), Growth_Rate (Slow 1, Moderate 2, Fast 3),
   Humidity_Need (Low 1, Medium 2, High 3).
2. `MinMaxScaler` on those six → values become **0.0 / 0.5 / 1.0**.
3. `Mature_Height_cm` copied as already-normalized float. PHP converts back with
   `real_cm = value * 9000`.
4. One-hot: `Categories_*` (9 columns) and `House_Type_*` (3 columns), float 0.0/1.0.
5. `ID` = 1..3170, `Image_URL` appended (base64 `data:image/jpeg;base64,...` strings).

Final columns (23):
```
ID, Scientific_Name, Common_Name,
Gardening_Level, Space_Required, Light_Condition, Water_Frequency, Growth_Rate, Humidity_Need,
Mature_Height_cm,
Categories_Climbers & Vines, Categories_Edibles & Herbs, Categories_Ferns,
Categories_Flowering Ornamentals, Categories_Foliage Plants, Categories_Palms,
Categories_Shrubs, Categories_Succulents & Cacti, Categories_Trees,
House_Type_High-rise (Apartment/Flat), House_Type_Landed (Terrace/Semi-D/Bungalow),
House_Type_Tradisional/Spacious (Village House),
Image_URL
```
Column names contain spaces, `&`, `/`, parentheses → **always backtick-quote them in SQL**.

**Decoding thresholds used in PHP** (vary slightly between files):
- ≤ 0.2 → low label, ≤ 0.6 (or ≤ 0.7 in `chatbot_backend.php`, `user_compare.php`,
  `user_view_recommendations.php`) → mid label, else high label.
- Gardening: Beginner Friendly / Intermediate Care / Expert Needed.
- Light: Low Light / Partial Sun / Full Direct Sun.
- Water: Low (Drought Tolerant) / Moderate (Weekly) / High (Consistently Wet).
- Search filters: Daily = `Water_Frequency > 0.6`, Weekly = 0.2–0.6, Monthly = ≤ 0.2 (same pattern for light, growth).

---

## 5. Database schema (`plantmatch_db`) — inferred from queries

There is no schema file; the following is reconstructed from the SQL in the PHP and notebooks.

### `plantmatch_ultimate_complete`
Plant catalogue, 3,170 rows, columns exactly as §4.3. Primary key `ID`.
(Imported from `final_encoded_plant_features.csv` — `hybrid.ipynb` cell 0 does this via SQLAlchemy.)

### `users`
| Column | Notes |
|---|---|
| `userID` | PK, auto-increment |
| `name` | display name (session `username`) |
| `email` | unique by app logic |
| `password` | `password_hash()` output |
| `role` | selected in `user_update_profile.php` (no admin features exist) |

### `preference` (one row per user — upserted with `ON DUPLICATE KEY UPDATE`, so `userID` is PK/UNIQUE)
`userID, houseType, gardeningLevel, availableSpace, lightCondition, growthRate,
plantPreference, waterFreq, humidityNeed, matureHeight (int cm, slider 20–1500, default 200)`
Values are stored as the **text** labels from the quiz (see §6.0).

### `favorite`
`userID, plantID, dateSaved` (some inserts omit `dateSaved`; others use `CURDATE()`).

### `save` ("Saved Garden")
`userID, plantID, saveTime`.

### `feedback`
`feedbackID (PK, auto), userID, plantID, rating (1–5), comment, feedbackDate`.
Some queries write the column as `plantid` (MySQL column names are case-insensitive, so it works).

### `recommendation`
`userID, plantID, rank (float score — reserved word, backticked), recTime`.
Only written by the unused `user_process_recommendation_form1.php`.

---

## 6. Recommendation logic

### 6.0 Quiz inputs (`user_recommendation_form.php`)
| Field | Options |
|---|---|
| `houseType` | High-rise (Apartment/Flat), Landed (Terrace/Semi-D/Bungalow), Tradisional/Spacious (Village House) |
| `gardeningLevel` | Beginner, Moderate, Expert |
| `availableSpace` | Small, Medium, Large |
| `lightCondition` | Full Sun, Partial Shade, Low Light |
| `growthRate` | Slow, Moderate, Fast |
| `plantPreference` | Flowering Ornamentals, Edibles & Herbs, Climbers & Vines, Trees, Foliage Plants, Ferns, Palms, Succulents & Cacti, Shrubs |
| `waterFreq` | Daily, Weekly, Monthly |
| `humidityNeed` | Low, Medium, High |
| `matureHeight` | range slider 20–1500 cm, step 10 |

### 6.1 Live PHP scoring — **this is what users actually see**
Used in `user_view_recommendations.php` (top 10) and `user_dashboard.php` (top 5).

1. `map_input_to_val($text)` turns a quiz answer into 0.0 / 0.5 / 1.0 by keyword:
   contains low/slow/shade/beginner/small/limited/compact/infrequent/dry → **0.0**;
   contains high/fast/sun/advanced/expert/large/spacious/frequent/wet/humid → **1.0**; else **0.5**.
   (Caveat: "Daily" and "Monthly" match neither list → both 0.5; "Weekly" → 0.5; "Partial Shade" → 0.0 because of "shade"; "Moderate" → 0.5.)
2. Score per plant (max 105):
   - Light `20 × (1 − |plant − user|)`
   - Water `20 × (1 − |plant − user|)`
   - Category match `+20` if the plant's `Categories_<pref>` column = 1
   - Gardening level, Space, Growth, Humidity: `10 × (1 − |plant − user|)` each
   - House type match `+5` (view page only)
3. Percentage = `round(score / 105 × 100)` (view page) or `score / 100 × 100` (dashboard), clamped 0–100.
4. Sort descending, tie-break by `ID` ascending (view page), take top 10 (view) / 5 (dashboard).
5. `matureHeight` is collected but **not used** by this scorer.

### 6.2 Alternate PHP hybrid (`user_process_recommendation_form1.php`, not wired up)
- Label-encodes user and plant into an 8-dim vector, multiplied by weights:
  houseType 1.0, gardeningLevel 1.5, availableSpace 1.0, **lightCondition 2.5**,
  plantPreference 0.8, **waterFreq 1.8**, growthRate 0.8, humidityNeed 1.2.
- `base = 0.8 × cosine(user, plant) + 0.2 × 1/(1 + |userHeight − plantHeight|)`.
- Bonuses: +0.07 if in `save`, +0.03 if in `favorite`; ×100.
- Personal rating adjust: 5★ +15, 4★ +7, 3★ 0, 2★ −15, 1★ −35; else global avg ≥4 → +5, ≤2 → −10.
- Clamp 0–100, top 10 inserted into `recommendation`.
- Its maps expect text values (`House_Type`, `Categories`, `'Beginner'`…) in the plant table,
  but the live table is numeric/one-hot → would need the text table to work.

### 6.3 Python pickle model (`recommend.py`, called by the active form handler)
- Loads `plantmatch_hybrid_model.pkl`; prints `[1..10]` as fallback if args or pickle missing.
- "Content" score: **pseudo-random** — uses `hash(str(plant_id) + preference) % N` to add 4.0 /
  2.5 / 2.0. Python's `hash()` of strings is randomized per process, so results are not
  deterministic and are not based on plant features.
- "Collaborative" score: if the user exists in `utility_matrix_train`, sum of content-similarity
  columns of rated plants weighted by rating.
- `final = alpha × content + (1 − alpha) × collab` (alpha 0.12) or content only if collab is all zero.
- Output is saved in the session but **never displayed** (view page recomputes, §6.1).
- PHP calls `python3`; on Windows/Laragon this is usually `python`, so `shell_exec` may fail
  silently → fallback `[1,2,3,4,5,6]`.

### 6.4 Notebook hybrid model (what the report/thesis describes)
- Content-based: cosine similarity over all numeric plant features (`content_sim_df`, 3170×3170).
- Collaborative: user × plant rating utility matrix from `feedback`, KNN (`metric='cosine'`,
  brute force, k = min(3 or 5, users)); neighbor ratings summed and min-max normalized.
- Hybrid: `alpha × CBF + (1 − alpha) × CF`; content score = mean similarity to plants the user
  rated ≥ 4.
- Train/test: 80/20 `train_test_split` on `feedback` (stratified on like = rating ≥ 4 in
  `Traindata.ipynb`). Data size is tiny: ~99–109 feedback rows from ~10 users.

---

## 7. Evaluation results — read carefully

- `hybrid.ipynb` computes real metrics with the loop over K/alpha. Example outputs:
  Top-5, alpha 0.5 → Precision@5 20.0%, Recall@5 48.6%, F1 28.3%, Hit Rate 83.3%;
  later epoch logs show Precision 66.7–80%, Recall 25–37.5%, F1 36–50%.
- (Removed on branch `cleanup/portfolio-ready`; kept here as history.) `Traindata.ipynb` cells 4–5 **did not compute metrics from the model**: the TP/TN/FP/FN values
  for each "epoch" are hard-coded (e.g. epoch 5: TP 18, TN 17, FP 1, FN 2), the alpha schedule
  is fixed, and cell 5 forces `Precision 0.95 / Recall 0.90 / F1 0.92 / alpha 0.12` if they are
  out of sync. Cell 4 even builds a mock `test_feedback` if the real one is missing. The
  classification-report table image is also typed in by hand. The "94.74% accuracy" message
  in cell 6 is a fixed string.
  → `peak_training_confusion_matrix.png` and `peak_training_classification_report.png` show
  these hard-coded numbers, not measured performance. If asked to report model accuracy, use
  the numbers that `hybrid.ipynb` actually computes (or recompute), and flag this to the user.

---

## 8. Known issues / technical debt

**Security**
1. ~~Hard-coded Groq API key~~ moved to git-ignored `config.php`. The old key is still in git
   history, so it must stay revoked. Never commit a key again.
2. **SQL injection risk**: most queries interpolate variables into SQL. `mysqli_real_escape_string`
   is used in many places, but not all (e.g. `user_compare.php` is escaped, but `$start/$limit`,
   column names built from `$_GET['cat']`/`$_GET['house']` in `user_search.php` are only escaped as
   strings inside backticks). Prefer prepared statements (already used in
   `user_process_recommendation_form.php` and `user_view_recommendations.php`).
3. GET-based deletes with no CSRF protection (`user_dashboard.php`, `user_feedbacklist.php`,
   `user_favorites.php`, `user_saved_garden.php`).
4. `chatbot_backend.php` is open to guests → anyone can spend the API quota.
5. Raw `mysqli_error()` and DB error messages are echoed to users.

**Bugs**
6. ~~`process_search.php` redirected with `?keyword=`~~ fixed: it now uses `?search=`.
7. ~~Palms typo on the dashboard~~ fixed.
8. `user_plant_details.php` / `search.php` / `user_search.php` category maps omit
   "Flowering Ornamentals" in some lists (`user_plant_details.php`, `user_search.php` filter list) → those plants show as "General Plant".
9. `user_process_recommendation_form.php` uses `python3` (Windows usually `python`) and its
   result is never used; `user_view_recommendations.php` ignores the session IDs.
10. `recommend.py` content score is based on `hash()` (random per run), not plant features.
11. ~~`map_input_to_val` gave Daily/Weekly/Monthly the same value~~ fixed: Daily 1.0, Weekly 0.5,
    Monthly 0.0, Partial 0.5 (in `user_view_recommendations.php` and `user_dashboard.php`).
12. `blog-*.php` and `portfolio-*.php` include a missing `header.php`.
13. `private_header.php` checks `username` while other pages check `user_id`.
14. `user_process_recommendation_form1.php` expects text-valued plant columns that the live table does not have.
15. Chatbot only knows the first 30 plants (`LIMIT 30`).
16. Inconsistent decoding thresholds (0.6 vs 0.7) between pages.

**Repo hygiene**
17. ~190 MB of data committed: two ~53 MB CSVs (base64 images), ~80 MB `.pkl`. Consider Git LFS
    or `.gitignore`.
18. `.gitignore`, `requirements.txt` and `README.md` added. Still no schema `.sql`.

---

## 9. Conventions for working on this codebase

- Keep the existing style: procedural PHP, one page per file, inline `<style>` blocks,
  Bootstrap classes, primary green colours `#104206` (navbar/footer) and `#198754` (accents).
- Every logged-in page starts with `include('private_header.php'); include('db.php');` and ends
  with `include('footer.php');`. Public pages use `public_header.php`.
- Session keys: `user_id` (int, `users.userID`), `username` (`users.name`), `flash_msg`,
  `chatbot_history`, `recommended_plant_ids`.
- Plant links: `user_plant_details.php?id=<ID>`; compare: `user_compare.php?ids=1,2,3` (max 5).
- When adding SQL, use prepared statements and backtick column names with special characters.
- When showing plant attributes, decode the 0/0.5/1 floats and one-hot columns into labels
  (copy an existing `map_*_label` helper; guard with `function_exists` because several pages
  define helpers with the same names).
- Height display: `value * 9000` cm; ≥ 100 cm shown in metres.
- Do not open the large CSVs / `.pkl` fully — read only headers or a few rows.
- Notebooks expect to run from the project root against the local Laragon MySQL.
