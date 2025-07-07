from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import os
import time
import json
import re

EMAIL = "tahiratalay90@gmail.com"
PASSWORD = "135797"

with open("urun_kodlari.json", "r", encoding="utf-8") as f:
    urun_kodlari = json.load(f)

sonuc = {}  # SKU: [resim_adı, resim_adı...]

def login(driver):
    driver.get("https://bayi.aksel.com.tr/Login")
    wait = WebDriverWait(driver, 10)
    wait.until(EC.presence_of_element_located((By.NAME, "UserName"))).send_keys(EMAIL)
    driver.find_element(By.NAME, "Password").send_keys(PASSWORD)
    driver.find_element(By.ID, "kt_login_signin_submit").click()
    wait.until(EC.url_changes("https://bayi.aksel.com.tr/Login"))
    print("Başarıyla giriş yapıldı.")

def sadece_resim_adlarini_kaydet(driver):
    wait = WebDriverWait(driver, 15)
    for kod in urun_kodlari:
        url = f"https://bayi.aksel.com.tr/ProductDetail/index/{kod}/?docType=11"
        driver.get(url)
        print(f"{kod} için arka plan resim adı aranıyor...")

        try:
            container = wait.until(EC.presence_of_element_located((By.CLASS_NAME, "zoomWindowContainer")))
            divs = container.find_elements(By.CLASS_NAME, "zoomWindow")
            resimler = []

            for div in divs:
                style = div.get_attribute("style") or ""
                m = re.search(r'background-image:\s*url\([\'"]?(.+?)[\'"]?\)', style)
                if m:
                    img_url = m.group(1)
                    # Dosya adını bul
                    dosya_adi = os.path.basename(img_url)
                    if dosya_adi:
                        resimler.append(dosya_adi)
            if resimler:
                sonuc[kod] = resimler
                print(f"{kod} -> {resimler}")
            else:
                print(f"{kod}: Hiç resim bulunamadı.")
        except Exception as e:
            print(f"{kod}: .zoomWindowContainer ya da arka plan resimleri bulunamadı. Hata: {e}")
        time.sleep(0.8)

def main():
    options = Options()
    options.add_argument("--start-maximized")
    driver = webdriver.Chrome(options=options)

    try:
        login(driver)
        sadece_resim_adlarini_kaydet(driver)
    finally:
        driver.quit()
        # Sonuçları JSON dosyasına kaydet
        with open("urun_resim_adlari.json", "w", encoding="utf-8") as f:
            json.dump(sonuc, f, ensure_ascii=False, indent=2)
        print("Bütün resim adları 'urun_resim_adlari.json' dosyasına kaydedildi.")

if __name__ == "__main__":
    main()
