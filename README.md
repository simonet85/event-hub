# Event Hub

Site événementiel (mariage, baptême, anniversaire…) : invitations personnalisées avec QR code, RSVP, programme, carte, livre d'or, galerie photo modérée, cagnotte et cadeaux payés via MoneyFusion, tableau de bord et exports.

**Documentation complète : [`docs/index.html`](docs/index.html)** (ouvrir le fichier dans un navigateur).

Démarrage rapide :

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
docker compose up -d
php artisan migrate && php artisan db:seed --class=DemoSeeder
npm run build
php artisan serve        # + php artisan queue:work dans un second terminal
```

---
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Traduction automatique (DeepL)

Le package local `simonet85/laravel-deepl` (dossier `../laravel-deepl`) remplit les fichiers `lang/*.json` à partir des clés `__('…')` du code. Le français est la langue source.

```bash
# .env : DEEPL_API_KEY=… (clé « Free » terminée par :fx) et DEEPL_SOURCE_LANG=fr
php artisan deepl:translate en --dry-run   # voir ce qui serait traduit
php artisan deepl:translate es             # créer / compléter lang/es.json
php artisan deepl:usage                    # caractères consommés
```

Pour proposer une nouvelle langue dans l'interface, ajoutez son code à `SetLocale::SUPPORTED` et au sélecteur `LocaleSwitcher.vue`. Les traductions existantes ne sont jamais écrasées sans `--force`.

### Développer les packages en local

`simonet85/laravel-moneyfusion` et `simonet85/laravel-deepl` sont installés depuis GitHub (dépôts `vcs`, branche `dev-main`) : modifier les dossiers voisins `../laravel-*` n'a **aucun effet** sur l'application. Pour répercuter un changement :

```bash
# 1. dans le package : commit + push
# 2. dans event-hub :
composer update simonet85/laravel-deepl      # ou simonet85/laravel-moneyfusion
```

Pour tester un package sans le pousser, déclarez temporairement un dépôt `path` (`"type": "path", "url": "../laravel-deepl"`) dans `composer.json`, sans le commiter. En production, préférez des versions étiquetées (`v1.0.0`, contrainte `^1.0`) à `dev-main`. En CI, un `COMPOSER_AUTH` avec un jeton GitHub évite la limite de requêtes anonymes.
