# R10 SMM Panel - Profesyonel Sosyal Medya Bayilik Paneli

![R10 SMM Banner](https://img.shields.io/badge/Version-1.0.0-blueviolet?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql)
![Design](https://img.shields.io/badge/Design-Glassmorphism-purple?style=for-the-badge)

R10 SMM, modern web teknolojileri ile geliştirilmiş, şık (premium) tasarıma sahip, tam kapsamlı bir SMM (Social Media Marketing) panel yazılımıdır. Kullanıcıların sosyal medya hizmetlerini kolayca satın alabileceği, yöneticilerin ise tüm sistemi merkezi bir panelden kontrol edebileceği şekilde tasarlanmıştır.

## 🚀 Öne Çıkan Özellikler

### 💎 Kullanıcı Tarafı
- **Premium Dashboard:** Harcama grafikleri, hızlı istatistikler ve modern üye arayüzü.
- **Canlı Bildirimler:** Sol altta beliren gerçek zamanlı satın alma bildirimleri (Sosyal Kanıt).
- **Gelişmiş Servis Listesi:** Kategori bazlı filtreleme, hızlı arama ve detaylı servis kartları.
- **Bakiye Sistemi:** Güvenli bakiye yükleme ve harcama takibi.
- **Destek Sistemi:** Admin ile anlık iletişim için bilet (ticket) sistemi.
- **Responsive Tasarım:** Mobil, tablet ve masaüstü cihazlar için tam uyumlu arayüz.

### 🛡️ Admin Paneli
- **Merkezi Yönetim:** Tüm kullanıcıları, siparişleri ve servisleri tek bir yerden yönetme.
- **Hizmet Yönetimi:** API üzerinden servis çekme, fiyatlandırma ve kategori düzenleme.
- **Bildirim Yönetimi:** Dashboard üzerinde görünecek canlı bildirimleri manuel ekleme/çıkarma.
- **Ayarlar Paneli:** Site başlığı, logo, iletişim bilgileri ve sistem ayarlarını kolayca güncelleme.
- **Destek Yönetimi:** Kullanıcılardan gelen talepleri yanıtlama ve çözümleme.

## 🛠️ Teknoloji Yığını
- **Backend:** PHP 8.x (PDO ile güvenli veritabanı yönetimi)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (Vanilla CSS + Premium Effects), JavaScript (ES6)
- **Kütüphaneler:** 
  - [SweetAlert2](https://sweetalert2.github.io/) (Modern uyarı pencereleri)
  - [FontAwesome 6](https://fontawesome.com/) (İkonlar)
  - [Chart.js](https://www.chartjs.org/) (İstatistik grafikleri)
  - [Google Fonts](https://fonts.google.com/) (Outfit & Plus Jakarta Sans)

## 📦 Kurulum

1. **Dosyaları Yükleyin:** Proje dosyalarını sunucunuza (public_html) yükleyin.
2. **Veritabanı Oluşturun:** MySQL üzerinden boş bir veritabanı oluşturun.
3. **Yapılandırma:** `config.php` dosyasını açarak veritabanı bilgilerinizi girin.
4. **Kurulum Sihirbazı:** `domain.com/install.php` adresine giderek gerekli tabloları otomatik oluşturun.
5. **Güvenlik:** Kurulum bittikten sonra sistem `install.php` dosyasına erişimi otomatik olarak `.htaccess` üzerinden engelleyecektir.

## 🔒 Güvenlik Notları
- SQL Injection saldırılarına karşı tüm sorgularda **PDO Prepared Statements** kullanılmıştır.
- XSS koruması için kullanıcı girdileri sanitize edilmektedir.
- Admin paneli yetkisiz erişimlere karşı sıkı bir kontrol mekanizmasına sahiptir.

## 📸 Ekran Görüntüleri
*(Buraya projenize ait ekran görüntülerini ekleyebilirsiniz)*

---
**Geliştirici:** shms
**Lisans:** Özel mülkiyet (Tüm hakları saklıdır)
