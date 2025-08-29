# استخدم صورة PHP 8.2-fpm كصورة أساسية. هذه الصورة خفيفة وتناسب تطبيقات الويب.
FROM php:8.2-fpm

# تثبيت الحزم المطلوبة من نظام التشغيل.
# apt-get update: لتحديث قائمة الحزم.
# apt-get install: لتثبيت git و libzip-dev و libpng-dev و curl وغيرها من التبعيات الضرورية.
# -y: للموافقة على التثبيت تلقائيًا.
RUN apt-get update && apt-get install -y \
    git \
    libzip-dev \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    curl

# تثبيت امتدادات PHP الضرورية لـ Laravel.
# docker-php-ext-install: أداة لتثبيت امتدادات PHP.
# pdo_mysql: للتواصل مع قاعدة بيانات MySQL (غيرها إذا كنت تستخدم قاعدة بيانات أخرى).
# zip: لدعم ملفات zip.
# gd: لمعالجة الصور.
RUN docker-php-ext-install pdo_mysql zip gd

# تثبيت Composer، مدير تبعيات PHP.
# curl: لتنزيل Composer.
# php --install-dir=/usr/local/bin --filename=composer: لتثبيت Composer في المسار الصحيح.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# تعيين مجلد العمل داخل الحاوية.
# كل الأوامر اللاحقة سيتم تنفيذها داخل هذا المجلد.
WORKDIR /var/www/html

# نسخ ملفات المشروع من جهازك إلى مجلد العمل في الحاوية.
# . : المصدر، أي جميع الملفات في مجلد المشروع الحالي.
# /var/www/html: الوجهة داخل الحاوية.
COPY . .

# تثبيت تبعيات المشروع باستخدام Composer.
# --no-dev: لاستبعاد حزم التطوير وتقليل حجم الصورة.
# --optimize-autoloader: لتحسين تحميل الفئات.
RUN composer install --no-dev --optimize-autoloader

# إعطاء الصلاحيات اللازمة لمجلدات storage و bootstrap/cache.
# chown -R www-data:www-data: لتغيير ملكية المجلدات للمستخدم www-data (المستخدم الافتراضي لـ PHP-FPM).
# هذه الخطوة مهمة لكي يتمكن Laravel من كتابة الملفات.
RUN chown -R www-data:www-data storage bootstrap/cache

# كشف المنفذ 9000. هذا هو المنفذ الذي يستمع عليه PHP-FPM.
EXPOSE 9000

# أمر بدء تشغيل الخدمة.
# هذا هو الأمر الذي سيتم تنفيذه عند تشغيل الحاوية.
CMD ["php-fpm"]
