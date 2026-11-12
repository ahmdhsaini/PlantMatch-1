import pdfplumber
import pandas as pd
import re

pdf_file = "488 page dataset.pdf"

all_rows = []

with pdfplumber.open(pdf_file) as pdf:

    for page_num, page in enumerate(pdf.pages):

        # Extract table with better settings
        table = page.extract_table(
            {
                "vertical_strategy": "lines",
                "horizontal_strategy": "lines",
                "snap_tolerance": 5,
                "join_tolerance": 5,
                "edge_min_length": 3,
                "min_words_vertical": 1,
                "min_words_horizontal": 1,
            }
        )

        if not table:
            continue

        for row in table:

            if not row:
                continue

            cleaned_row = []

            for cell in row:

                if cell is None:
                    cleaned_row.append("")
                    continue

                # Join wrapped lines into one line
                cell = re.sub(r"\s*\n\s*", " ", str(cell))

                # Remove excessive spaces
                cell = re.sub(r"\s+", " ", cell).strip()

                cleaned_row.append(cell)

            all_rows.append(cleaned_row)

df = pd.DataFrame(all_rows)

df.to_csv("flora_extracted_improved.csv", index=False)

print("Done")