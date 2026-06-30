# Tutash Hududlar Reestri — MVP (demo)

"Thudud" — noturar (tijorat) ob'ektlari egalariga tegishli ob'ektga **tutash (qo'shni) hududdan**
foydalanish uchun ariza berish, arizani bosqichma-bosqich mas'ul xodimlardan o'tkazish (pipeline),
tasdiqlangan ariza asosida **shartnoma tuzish** va shartnoma bo'yicha **oylik to'lov grafigi + invoyslar**
shakllantirish tizimi.

> Bu **demo / ko'rsatish uchun** MVP. Hech qanday tashqi integratsiya (OneID, E-IMZO, to'lov tizimi) **yo'q**.
> Hammasi sof **Laravel 11 + Blade** bilan ishlaydi. Ma'lumotlar faker (soxta) bilan to'ldiriladi.

---

## Texnologiya

- **Laravel 11**, PHP 8.2+
- **Blade** (server-rendered sahifalar, SPA emas)
- **SQLite** (demo uchun, hech qanday DB server kerak emas)
- Toza, mustaqil **CSS** (`public/css/app.css`) — CDN/Node build **kerak emas**
- Rol/ruxsat: **spatie/laravel-permission**
- Status/action qiymatlari uchun **PHP enum** (`app/Enums`)
- Validatsiya **Form Request** orqali, avtorizatsiya **Policy** orqali

---

## O'rnatish

Talab: **PHP 8.2+**, **Composer**, PHP `pdo_sqlite` kengaytmasi.

```bash
# 1. Bog'liqliklarni o'rnatish
composer install

# 2. .env tayyorlash (agar yo'q bo'lsa)
cp .env.example .env
php artisan key:generate

# 3. SQLite faylini yaratish
#   Linux/Mac:
touch database/database.sqlite
#   Windows PowerShell:
#   New-Item -ItemType File database/database.sqlite

# 4. Migratsiya + demo ma'lumotlar
php artisan migrate:fresh --seed

# 5. Serverni ishga tushirish
php artisan serve
```

So'ng brauzerda oching:

- **Lending sahifa (ochiq):** http://localhost:8000/
- **Tizimga kirish:** http://localhost:8000/login

> Loyiha allaqachon o'rnatilgan bo'lsa, demo ma'lumotlarni qayta tiklash uchun:
> `php artisan migrate:fresh --seed`

---

## Test foydalanuvchilar

Barcha parollar: **`password`**

| Email | Rol | Vazifasi |
|---|---|---|
| `applicant@test.uz`  | Mulkdor              | Ariza yaratadi, o'z arizalari va shartnomalarini ko'radi |
| `moderator@test.uz`  | Moderator            | Yangi arizani ko'radi → mas'ul xodimga uzatadi / bekor qiladi |
| `officer@test.uz`    | Mas'ul xodim         | O'lchov (survey) to'ldiradi → ishchi guruhga uzatadi; xulosa yozib tasdiqlaydi |
| `workgroup@test.uz`  | Ishchi guruh         | Izoh yozadi → keyingi bosqichga uzatadi / qaytaradi / bekor qiladi |
| `deputy@test.uz`     | O'rinbosar           | Yakuniy tasdiqlash / bekor qilish; shartnoma nazorati |
| `head@test.uz`       | Rahbar               | Zam rahbar bilan teng vakolat |
| `lawyer@test.uz`     | Yurist               | Shartnomalarni kuzatadi, to'xtatadi, bekor qiladi (pipeline'da **yo'q**) |
| `compliance@test.uz` | Komplayens           | Shartnomalarni monitoring qiladi, to'xtatadi (pipeline'da **yo'q**) |

Qo'shimcha mulkdorlar: `applicant1@test.uz` … `applicant8@test.uz` (parol `password`).

> Eslatma — hududiy filtr:
> - **`officer@test.uz` (mas'ul xodim)** — **Mirzo-Ulug'bek tumani**ga biriktirilgan: faqat o'z tumani
>   arizalarini ko'radi (hududiy filtr namunasi).
> - **Boshqa xodimlar** (`moderator`, `workgroup`, `deputy`, `head`, `lawyer`, `compliance`) — respublika
>   darajasida (`district_id = null`): barcha tumanlarni ko'radi. Shuning uchun ochiq formadan **istalgan
>   shahar/tumanni** tanlab yuborsangiz ham, ariza moderator panelida paydo bo'ladi.
>
> Filtr `Application::scopeForDistrictOf` da: foydalanuvchiga `district_id` berilsa — faqat o'sha tuman.

---

## Pipeline (sipochka)

```
draft (mulkdor yaratadi)
  → moderation         (moderator: forward / reject)
  → responsible_review (mas'ul xodim: o'lchov + ma'lumot to'ldiradi → forward / return / reject)
  → deputy_review      (zam rahbar / o'rinbosar: forward / return / reject)
  → head_review        (rahbar: approve / return / reject)
  → approved           → shartnoma + 12 oylik to'lov grafigi + invoyslar AVTOMATIK yaratiladi
  → rejected           (istalgan bosqichda bekor qilinishi mumkin)
```

Qoidalar:
1. **Bosqichni sakrab o'tib bo'lmaydi** — ruxsat etilgan o'tishlar `app/Enums/ApplicationStage.php`
   ichidagi transition matritsasida qat'iy tekshiriladi (`ApplicationWorkflowService`).
2. **Ariza rolga o'tadi**, aniq foydalanuvchiga emas — bir bosqichdagi arizani o'sha tumandagi
   tegishli roldagi har qanday xodim ko'rishi/harakatlantirishi mumkin.
3. **Har bir harakat audit jurnaliga yoziladi** (`application_transitions`): kim, qaysi bosqichdan
   qaysi bosqichga, qanday harakat, izoh, qachon. Bu ariza tafsiloti sahifasida **tarix (timeline)**
   sifatida ko'rinadi (kimdan kimga o'tgani, mas'ul xodim va arizachi ma'lumotlari bilan).
4. **Hududiy filtr** — xodim faqat o'z tumani arizalarini ko'radi.

> E-IMZO **yo'q** — har bir harakat tugma bosish bilan amalga oshadi.

**Mas'ul xodim survey formasi** (responsible_review bosqichi): o'lchov maydonlaridan tashqari
- **rasm yuklash** (bir nechta, `public/uploads/surveys/` ga saqlanadi — `storage:link` kerak emas);
- **xaritada ijaraga olinayotgan maydonni belgilash** (poligon/to'rtburchak chizish).
  Xarita — **Leaflet + OpenStreetMap** (API kalit kerak emas). Belgilangan maydon GeoJSON sifatida
  saqlanadi va ariza tafsilotida (faqat ko'rish rejimida) xaritada ko'rsatiladi.
  > Google Maps JS API endi majburiy API-kalit + billing talab qiladi — shuning uchun demoda
  > kalitsiz ishlaydigan OpenStreetMap ishlatilgan; kerak bo'lsa keyin Google'ga almashtirish mumkin.
  > Xarita plitalari (tiles) internet orqali yuklanadi.

---

## Avtomatik mantiq

- **Ariza tasdiqlangach (`approved`):** bitta DB tranzaksiyada shartnoma, 12 oylik `payment_schedules`
  va har biri uchun `invoices` yaratiladi (`ContractService::createFromApplication`).
- **Penya hisoblash:** `payments:check` artisan komandasi `due_date < bugun` va `status != paid`
  bo'lgan to'lovlarni `overdue` qiladi va penyani hisoblaydi:
  `penalty = amount * penalty_rate/100 * kechikkan_kunlar` (faqat **faol** shartnomalar uchun).
- **Shartnoma to'xtatilsa/bekor qilinsa:** kelajakdagi `pending` invoyslar `cancelled` bo'ladi.

```bash
php artisan payments:check
# yoki muayyan sanaga:
php artisan payments:check --date=2026-12-31
```

---

## Lending sahifa + ochiq ariza

Bosh sahifa (`/`) — tadbirkorlar uchun lending sahifa. Eng pastda **ariza topshirish formasi**:
*ism, familiya, PINFL, ob'ekt kadastri, firma nomi, shahar/viloyat → tuman → mahalla → ko'cha, uy raqami, telefon*.

- **Hudud tanlovi** kaskad (Shahar → Tuman → Mahalla → Ko'cha) — butun O'zbekiston bo'yicha
  (14 viloyat, ~200 tuman, 1500+ mahalla, 3000+ ko'cha). Hammasi **selectable** (dropdown).
  Ma'lumot **AJAX** orqali yuklanadi (`/geo/regions/{id}/districts`, `/geo/districts/{id}/mahallas`,
  `/geo/districts/{id}/streets`) — shuning uchun sahifa yengil qoladi.
- **Input maskalar:** PINFL faqat raqam (max 14), kadastr avtomatik `NN:NN:NN:NN:NN:NNNN`,
  telefon `+998 XX XXX XX XX`.

Yuborilganda tizim tadbirkor va ob'ektni topadi/yaratadi, arizani **moderatsiya** bosqichiga uzatadi
— u darhol tegishli tuman **moderator panelida** paydo bo'ladi.

---

## Sahifalar

| Sahifa | Kim ko'radi |
|---|---|
| Bosh sahifa (dashboard, rolga mos kartalar) | Hammasi |
| Arizalar ro'yxati (filtr: bosqich/holat/qidiruv, pagination) | Mulkdor + pipeline rollari |
| Ariza yaratish | Mulkdor |
| Ariza tafsiloti (pipeline tarixi + harakatlar + survey forma) | Tegishli rollar |
| Shartnomalar ro'yxati / tafsiloti (grafik, penya, harakatlar) | Mulkdor + nazorat rollari |
| Monitoring / hisobot | Barcha xodimlar |

---

## Loyiha tuzilmasi (asosiy)

```
app/
  Enums/              # ApplicationStage (transition matritsasi), Status'lar, Role, Action'lar
  Services/
    ApplicationWorkflowService.php   # pipeline: transition + ruxsat + audit
    ContractService.php              # shartnoma + grafik + invoyslar + nazorat harakatlari
  Policies/           # ApplicationPolicy, ContractPolicy
  Http/
    Controllers/      # Auth, Dashboard, Application, Contract, Monitoring, Public
    Requests/         # Form Request validatsiya
  Console/Commands/CheckPayments.php # payments:check
database/
  migrations/         # barcha jadvallar
  seeders/            # RolePermission, Geography, User, DemoData
resources/views/      # Blade (layouts, applications, contracts, monitoring, landing, auth)
public/css/app.css    # mustaqil UI (CDN'siz)
```

---

## Ma'lumotlar bazasi jadvallari

`regions, districts, mahallas, objects, object_tenants, applications, application_surveys,
adjacent_areas, application_transitions, contracts, contract_actions, payment_schedules, invoices`
(+ `users` kengaytirilgan, + spatie permission jadvallari).
