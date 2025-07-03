from bs4 import BeautifulSoup
import csv
import re

with open("veri_lasertoner_pantum.html", encoding="utf-8") as f:
    html = f.read()

soup = BeautifulSoup(html, "html.parser")
urunler = []

for index, urun in enumerate(soup.find_all("div", class_="kt-portlet")):
    try:
        urun_adi = urun.find("a", class_="kt-widget__username").find("span").text.strip()
    except:
        urun_adi = ""

    try:
        desc_div = urun.select_one("div.kt-widget__desc")
        if desc_div:
            full_text = desc_div.get_text(separator=" ", strip=True)
            match = re.search(r"([0-9]+(?:[.,][0-9]+)?)\s*USD", full_text)
            fiyat_usd = match.group(1) if match else ""
        else:
            fiyat_usd = ""
    except:
        fiyat_usd = ""

    try:
        stok_kodu_raw = urun.find(text=lambda t: "Stok Kodu" in t)
        stok_kodu = stok_kodu_raw.split(":")[1].strip() if stok_kodu_raw else ""
    except:
        stok_kodu = ""

    urun_tipi = "Lazer Toner"

    # DEBUG: Konsola yaz
    print(f"[{index+1}] {urun_adi} | {fiyat_usd} | {stok_kodu} | {urun_tipi}")

    urunler.append([
        urun_adi,
        fiyat_usd,
        stok_kodu,
        urun_tipi
    ])

with open("urunler.csv", "w", newline="", encoding="utf-8") as f:
    writer = csv.writer(f)
    writer.writerow([
        "urun_adi",
        "fiyat",
        "stok_kodu",
        "urun_tipi"
    ])
    writer.writerows(urunler)

print("✅ Bitti! CSV dosyan hazır.")
