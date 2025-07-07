from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time
import csv
import re  # <-- bunu importların yanına ekle
import json

import os  # <-- Diğer importların altına ekle

tamamlananlar_file = "tamamlananlar.json"
if os.path.exists(tamamlananlar_file):
    with open(tamamlananlar_file, "r", encoding="utf-8") as f:
        tamamlanan_urunler = set(json.load(f))
else:
    tamamlanan_urunler = set()



def fiyat_usd_cek(fiyat_str):
    m = re.search(r"([\d.,]+)\s*USD", fiyat_str)
    return m.group(1).replace(',', '') if m else ""



EMAIL = "tahiratalay90@gmail.com"
PASSWORD = "135797"

with open("urun_kodlari.json", "r", encoding="utf-8") as f:
    urun_kodlari = json.load(f)

def login(driver):
    driver.get("https://bayi.aksel.com.tr/Login")
    wait = WebDriverWait(driver, 10)
    wait.until(EC.presence_of_element_located((By.NAME, "UserName"))).send_keys(EMAIL)
    driver.find_element(By.NAME, "Password").send_keys(PASSWORD)
    driver.find_element(By.ID, "kt_login_signin_submit").click()
    wait.until(EC.url_changes("https://bayi.aksel.com.tr/Login"))
    print("Başarıyla giriş yapıldı.")

def urunleri_isle(driver):
    wait = WebDriverWait(driver, 200)

    
    fieldnames = [
        "urun_adi", "Fiyat_str", "gelis_fiyat_usd", "baski_kapasitesi",
        "stok", "kendi_depomuz", "stok_kodu", "yazici_uyumluluk_listesi"
    ]
    csv_exists = os.path.exists("urunler.csv")
    with open("urunler.csv", mode="a", encoding="utf-8", newline="") as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        if not csv_exists:
            writer.writeheader()

        for kod in urun_kodlari:
            if kod in tamamlanan_urunler:
                print(f"{kod} zaten kaydedilmiş, atlanıyor.")
                continue
            url = f"https://bayi.aksel.com.tr/ProductDetail/index/{kod}/?docType=11"
            driver.get(url)
            print(f"{kod} sayfasına gidildi, içerik çekiliyor...")

            try:
                urun_adi = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".kt-widget__username"))).text
            except Exception as e:
                print(f"{kod} ürünü sayfasında ürün adı bulunamadı. Hata: {e}")
                print("10 saniye bekleniyor, bir sonrakine geçiliyor...")
                time.sleep(10)
                urun_adi = ""
                continue

            # Bilgilerin olduğu container'ı al
            try:
                info_block = driver.find_element(By.CSS_SELECTOR, ".kt-widget__subhead")
                info_text = info_block.get_attribute("innerText")
                satirlar = info_text.split("\n")

                fiyat = ""
                baski_kapasitesi = ""
                izmir_stok = ""
                istanbul_stok = ""
                stok_kodu = ""
                fiyat_usd = ""

                for satir in satirlar:
                    if "Fiyat:" in satir:
                        fiyat = satir.replace("Fiyat:", "").strip()
                        fiyat_usd = fiyat_usd_cek(fiyat)
                    elif "Baskı Kapasitesi:" in satir:
                        tmp = satir.replace("Baskı Kapasitesi:", "").strip()
                        if tmp == "":
                            idx = satirlar.index(satir)
                            if idx + 1 < len(satirlar):
                                baski_kapasitesi = satirlar[idx + 1].strip()
                            else:
                                baski_kapasitesi = ""
                        else:
                            baski_kapasitesi = tmp
                        # Virgül ve noktayı sil
                        baski_kapasitesi = re.sub(r"[.,]", "", baski_kapasitesi)
                    elif "İzmir Stok Durumu" in satir:
                        izmir_stok = satir.split(":")[-1].strip()
                        m = re.search(r"\d+", izmir_stok)
                        izmir_stok = m.group(0) if m else "0"
                    elif "İstanbul Stok Durumu" in satir:
                        istanbul_stok = satir.split(":")[-1].strip()
                        m = re.search(r"\d+", istanbul_stok)
                        istanbul_stok = m.group(0) if m else "0"
                    elif "Stok Kodu:" in satir:
                        stok_kodu = satir.replace("Stok Kodu:", "").strip()
            except Exception as e:
                print(f"{kod} sayfasında bilgi bloğu bulunamadı. Hata: {e}")
                fiyat = baski_kapasitesi = izmir_stok = istanbul_stok = stok_kodu = ""

            # Uyumluluk Modelleri
            modeller = []
            try:
                rows = driver.find_elements(By.CSS_SELECTOR, "#reportTable tbody tr")
                for row in rows:
                    marka = row.find_element(By.CSS_SELECTOR, "td:nth-child(1)").text.strip()
                    model_listesi = row.find_element(By.CSS_SELECTOR, "td:nth-child(2)").text.strip()
                    modeller.append(f"{marka}: {model_listesi}")
            except Exception as e:
                print(f"{kod} sayfasında uyumluluk modelleri bulunamadı. Hata: {e}")

            uyumluluk_str = "; ".join(modeller) if modeller else ""

            print(f"Ürün Adı: {urun_adi}")
            print(f"Fiyat: {fiyat}")
            print(f"gelis_fiyat_usd: {fiyat_usd}")
            print(f"Baskı Kapasitesi: {baski_kapasitesi}")
            print(f"İzmir Stok: {izmir_stok}")
            print(f"İstanbul Stok: {istanbul_stok}")
            print(f"Stok Kodu: {stok_kodu}")
            print(f"Uyumluluk Modelleri: {uyumluluk_str}")
            print("-" * 40)

            row ={
                "urun_adi": urun_adi,
                "Fiyat_str": fiyat,
                "gelis_fiyat_usd": fiyat_usd,
                "baski_kapasitesi": baski_kapasitesi,
                "stok": izmir_stok,
                "kendi_depomuz": istanbul_stok,
                "stok_kodu": stok_kodu,
                "yazici_uyumluluk_listesi": uyumluluk_str,
                
            }

            row = {k: str(v).strip() for k, v in row.items()}
            writer.writerow(row)
            tamamlanan_urunler.add(kod)
            with open(tamamlananlar_file, "w", encoding="utf-8") as f:
                json.dump(list(tamamlanan_urunler), f, ensure_ascii=False)

           

            time.sleep(2)

def main():
    options = Options()
    options.add_argument("--start-maximized")
    driver = webdriver.Chrome(options=options)

    try:
        login(driver)
        urunleri_isle(driver)
    finally:
        driver.quit()

if __name__ == "__main__":
    main()
