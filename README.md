# PlantMatch

A plant recommendation website built for my Final Year Project (Bachelor of Computer Science, UiTM, 2026).

Users answer a short quiz about their home (house type, light, space, how often they can water, their gardening experience) and PlantMatch suggests plants that suit those conditions. The plant data comes from a 488-page Singapore flora dataset that I extracted and cleaned myself.

<!-- Add screenshots to docs/screenshots/ and uncomment:
![Quiz](docs/screenshots/quiz.png)
![Recommendations](docs/screenshots/recommendations.png)
-->

## Features

- 9-step quiz on house type, light, water, space, skill level, humidity, growth rate, plant type and height
- Top 10 plant matches with a match percentage
- Plant search with filters (category, light, water, growth rate, house type)
- Favourites, side-by-side comparison of up to 5 plants, and a saved garden
- Ratings and reviews on each plant
- FloraBot, a chatbot that answers plant care questions (Groq API, Llama 3.3)

## Tech stack

| Part | Tools |
|---|---|
| Website | PHP, MySQL, Bootstrap 5 |
| Data pipeline | Python, pdfplumber, pandas, scikit-learn |
| Recommendation research | Python, scikit-learn (cosine similarity, KNN), Jupyter |
| Chatbot | Groq API (`llama-3.3-70b-versatile`) |

## How the recommendations work

**On the website**, each plant is scored against the quiz answers. Light and water count the most, then plant type, then skill level, space, growth rate and humidity, plus a small bonus when the house type matches. The plants with the highest scores are shown first.

**In the notebooks**, I built and tested a hybrid model:

- **Content-based filtering:** cosine similarity between plants, using their encoded care features (3,170 x 3,170 similarity matrix).
- **Collaborative filtering:** user-based KNN on the ratings users gave to plants.
- **Hybrid:** a weighted mix of the two, so new users with no ratings still get content-based results.

### Results

Tested on an 80/20 split of the ratings collected during testing (about 100 ratings from 10 users), top-5 recommendations, equal weighting:

| Metric | Result |
|---|---|
| Precision@5 | 20.0% |
| Recall@5 | 48.6% |
| F1-score | 28.3% |
| Hit rate | 83.3% |

The rating data is small, so the collaborative part has little to learn from. Collecting more ratings is the main way to improve these numbers.

## Data pipeline

```
488-page flora PDF
  -> tables extracted with pdfplumber            (flora_extracted_improved.csv)
  -> repeated headers removed, 3,170 plants kept  (flora_final_cleaned.csv)
  -> names and text cleaned                       (flora_final_cleaned1/2.csv)
  -> care features added from each plant's category (flora_topic_focused.csv)
  -> features encoded and scaled                  (final_encoded_plant_features.csv)
  -> imported into MySQL
```

Steps are in `data_preprocessing.ipynb` (and `convert_plants.py` for the PDF step). The hybrid model and its evaluation are in `hybrid.ipynb` and `collaborative_filtering.ipynb`.

## Run it locally

1. Install [Laragon](https://laragon.org/) (or XAMPP) and put this folder in its `www` folder.
2. Start Apache and MySQL, then create a database called `plantmatch_db`.
3. Import the plant data: run the first cell of `hybrid.ipynb`, which loads `final_encoded_plant_features.csv` into MySQL.
4. Create the user tables (`users`, `preference`, `favorite`, `save`, `feedback`). The columns are listed in `CLAUDE.md`, section 5.
5. For the chatbot, copy `config.example.php` to `config.php` and add your Groq API key.
6. Open `http://localhost/<folder-name>/index.php`.

For the notebooks: `pip install -r requirements.txt`.

## What I would improve next

- Use the hybrid model's results directly on the website instead of the separate scoring
- Move all SQL queries to prepared statements
- Collect more ratings so collaborative filtering has more to work with

## Author

Ahmad Husaini bin Zainal 'Abidin, [github.com/ahmdhsaini](https://github.com/ahmdhsaini)
