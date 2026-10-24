# Skuul — mémoire projet

Application de gestion scolaire multi-campus : Laravel 13, Livewire 4, Tailwind 4, PHP 8.5, MySQL, Redis, Vite (bun).

## Environnement local (lerd)

- Site : http://skuul.test — services `lerd-mysql`, `lerd-redis`, `lerd-mailpit`
- Workers : `lerd-queue-skuul`, `lerd-schedule-skuul`, `lerd-vite-skuul` (bun)
- Bases : `skuul` (dev, seedée) et `testing` (migrée)
- Comptes de démo : `super@example.com`, `admin@example.com`, `teacher@example.com`, `student@example.com` — mot de passe `password`
- Artisan et les tests passent par `lerd artisan` (pas Sail : `phpunit.xml` et `.env.testing` sont câblés sur `lerd-mysql`)

Commandes courantes :

```bash
lerd artisan migrate
lerd artisan db:seed --force
lerd artisan migrate --force --env=testing
lerd artisan test --compact tests/Feature/XxxTest.php
```

## Fichiers

- `index.md` — ce fichier
- `i18n.md` — traduction / Lingo.dev (config, commande, pièges)

## Règles et pièges connus

- Les chaînes traduisibles sont écrites en clair dans le code : `__('English sentence')`, la clé ET la valeur de `lang/en.json`. Les langues cibles sont des fichiers JSON (`lang/fr.json`), pas des dossiers PHP.
- Ne jamais mettre dans `lang/en.json` une clé contenant `$`, `{{` ou `}}` : ce sont des chaînes interpolées par Blade, non traduisibles.
- Les textes d'interface doivent être extractionnables : pas de concaténation (`__('Edit ') . $name`), sinon la chaîne n'arrive jamais dans le catalogue.
- `lang/en/*.php` (auth, pagination, passwords, validation) et `lang/vendor/**` sont des fichiers du framework : hors périmètre de la traduction.
- Les tests rejouent migrations + seeders à chaque test : une suite de quelques tests prend plusieurs minutes, lancer un fichier ciblé.
