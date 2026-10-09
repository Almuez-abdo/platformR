# platformR — منصة تعليمية

منصة PHP + MySQL (تسجيل/دخول، محاضرات، كورساتي، اشتراكات) — بدون أي مكتبات خارجية.

## المتطلبات
- PHP 8.x + MySQL/MariaDB (XAMPP) — لا يحتاج Composer

## التشغيل
1. انسخ المشروع إلى `htdocs/platformR`
2. أنشئ قاعدة بيانات `educational_platform` (حسب `config/database.php`):
   ```sql
   CREATE DATABASE educational_platform CHARACTER SET utf8mb4;
   ```
   ثم استورد ملف `educational_platform.sql`:
   - phpMyAdmin ← استيراد ← `educational_platform.sql`
3. إعداد الاتصال في `config/database.php` (الوضع الافتراضي: `root` بدون كلمة سر)
4. افتح: `http://localhost/platformR/register.php` لإنشاء حساب ثم `login.php`
