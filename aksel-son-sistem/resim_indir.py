import json
import os
import requests

# Kayıt klasörü
KLASOR = "resimler"
os.makedirs(KLASOR, exist_ok=True)

# JSON'u yükle
with open("urun_resim_adlari.json", "r", encoding="utf-8") as f:
    veriler = json.load(f)

base_url = "https://bayi.aksel.com.tr/Images/ProductImages/Orjinal/"

# İşlem
for sku, resimler in veriler.items():
    if not resimler:
        print(f"⚠ {sku} için resim bulunamadı.")
        continue

    orijinal_resim_adi = resimler[0]  # İlk resmi al
    url = base_url + orijinal_resim_adi
    hedef_yol = os.path.join(KLASOR, f"{sku}.jpg")

    # Zaten indirilmişse geç
    if os.path.exists(hedef_yol):
        print(f"Zaten var: {sku}.jpg")
        continue

    try:
        print(f"İndiriliyor: {url}")
        response = requests.get(url, timeout=10)
        if response.status_code == 200:
            with open(hedef_yol, "wb") as f:
                f.write(response.content)
            print(f"✔ Kaydedildi: {sku}.jpg")
        else:
            print(f"⚠ Hatalı yanıt ({response.status_code}) → {url}")
    except Exception as e:
        print(f"❌ Hata ({sku}): {e}")
