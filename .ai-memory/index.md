# Skuul — mémoire projet

Application de gestion scolaire multi-campus : Laravel 13, Livewire 4, Tailwind 4, PHP 8.5, MySQL, Redis, Vite (bun).

## Marque

- Marque commerciale : **Kola** (nom camerounais). `APP_NAME`, les vues, `lang/en.json`/`lang/fr.json` et les messages d'installation disent Kola.
- Le code, la base, les commandes et le dépôt restent `skuul` : ne jamais renommer `skuul:*`, la base `skuul`, le dépôt `yungifez/skuul` ni l'enregistrement de vérification `_skuul-verification`. Les messages de `skuul:update` parlent volontairement de Skuul (projet amont).

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

## Argent et caisse

- Devise `XAF` (`APP_CURRENCY`), fuseau `Africa/Douala` (`APP_TIMEZONE`). `money_text()` formate ; ne jamais formater à la main.
- `App\Casts\Money` stocke des **entiers en unité mineure** (Brick). Une colonne montant n'est pas un float, même si une migration voisine écrit `decimal(15,2)`.
- `StudentPayment` et `CashDeposit` sont append-only : `booted()` lève sur update/delete. Corriger = contre-écriture (`ReversePayment`), jamais une modification.
- Chaque `StudentPayment` reçoit un `receipt_number` séquentiel par école (`School` verrouillé, `max + 1`).
- `CashSession` = tiroir d'un caissier : `OpenCashSession` (un seul tiroir ouvert par caissier), `CloseCashSession` (comptage + écart). Un paiement `cash` rejoint le tiroir ouvert ; `expected()` = float + encaissements, `variance()` = compté − attendu.
- Limite connue : les annulations ne portent pas de `cash_session_id`, elles n'entrent donc pas dans l'écart de caisse.

## Notation et bulletin

- Le coefficient est porté par `course_offerings.coefficient` (pas par la matière : un même sujet pèse différemment selon la classe/série).
- `PublishReportCard::payload()` calcule la moyenne pondérée et expose `summary` (`average_percentage`, `average_out_of_20`, `total_coefficients`) ; chaque matière porte `coefficient` et `out_of_20`.
- Le bulletin s'affiche en `/20` (convention camerounaise), les pourcentages restent internes.

## Référentiel scolaire camerounais

- `App\Enums\EducationSubsystem` (francophone / anglophone / bilingue) et `App\Enums\SchoolCategory` (maternel, primaire, secondaire général, technique, supérieur, centre de formation).
- `App\Services\School\CameroonStructure::groups($category)` porte la carte scolaire officielle : groupes (maternel, primaire, 1er/2nd cycle, technique) et listes de classes **par sous-système**, car les deux routes ne s'alignent pas (4 ans de 1er cycle en FR, 5 en EN).
- `lerd artisan school:cameroon-structure --school=1 --subsystem=bilingual --category=secondary-general` crée les niveaux ; idempotent car la clé est `(school_id, code)`.
- Chaque classe porte son nom dans sa langue (`6e` ou `Form 1`) et reste une ligne distincte : les deux sous-systèmes coexistent sans duplication fonctionnelle.
- Reste à faire : l'étape d'installation qui propose ce référentiel selon le sous-système et la catégorie choisis.

## Calendrier scolaire camerounais

- `lerd artisan academic:year-cameroon --school=1 --year=2025 --start=2025-09-08` ouvre l'année : 3 trimestres, 2 séquences par trimestre, 6 fenêtres d'examen, les deux interruptions. Idempotent.
- `AcademicPeriodType::Sequence` est une sous-période enseignante ; les séquences sont des `academic_periods` enfants du trimestre (`parent_id`) — aucun nouveau modèle.
- Les dates se calculent (`App\Services\School\CameroonCalendar`) : rentrée = premier lundi après le 1er septembre, fin 31 juillet, coupure de Noël au 19 décembre jusqu'au premier lundi de janvier, coupure de Pâques de `Pâques - 3 j` à `Pâques + 15 j` (comput grégorien). Vérifié : 2025/2026 redonne exactement les dates officielles (08/09/2025, 19/12→05/01, 02/04→20/04/2026).
- Les interruptions sont des `Holiday` à la racine (pas enfants d'un trimestre) et `isTeaching()` vaut `false` : aucune présence attendue.
- **Manque connu** : le bulletin publie pour une seule période. Une moyenne de trimestre agrégeant les 2 séquences n'existe pas encore ; chaque séquence se publie séparément.

## Bulletin par compétences (APC)

- Le mode d'évaluation est porté par le **niveau**, pas par l'élève ni par l'école : `academic_levels.assessment_mode` (enum `App\Enums\AssessmentMode` : `marks` / `competency`). L'élève hérite du mode via sa section (`academic_cycle_sections.academic_level_id`).
- Tables : `competency_domains` (école, `academic_level_id` optionnel = domaine propre au niveau, `name`/`name_en`, `position`), `competencies`, `competency_assessments` (unique élève + compétence + période).
- `App\Enums\CompetencyLevel` : `acquired` (Acquis), `in_progress` (En cours), `not_acquired` (Non acquis).
- `PublishReportCard` bascule tout seul : en mode compétences, `average_percentage = null`, `results = []`, bloc `competencies` groupé par domaine, bilan `competencies_assessed` ; publier sans aucune évaluation lève `InvalidValueException`.
- Écran de saisie : route `competencies.assess` → `/dashboard/students/{student}/competencies`, Livewire `App\Livewire\AssessCompetencies`. Un niveau par compétence, commentaire libre, choix vide = suppression de la ligne. Permission : `update student` (aucune permission nouvelle).
- **Piège test** : `Livewire::test(AssessCompetencies::class, ['studentRecord' => $student])` rend une page vide dans ce dépôt (le modèle passé en paramètre n'arrive pas dans `mount`) et le `GET` HTTP du même écran renvoie 403 ou 302 selon l'ordre des tests. L'écran n'est donc couvert par aucun test automatisé ; les tests du bulletin (`tests/Feature/CompetencyReportCardTest.php`) passent.
- Aucun référentiel de domaines/compétences n'est seedé : chaque école les crée.

## Fichiers

- `index.md` — ce fichier
- `i18n.md` — traduction / Lingo.dev (config, commande, pièges)

## Règles et pièges connus

- Les chaînes traduisibles sont écrites en clair dans le code : `__('English sentence')`, la clé ET la valeur de `lang/en.json`. Les langues cibles sont des fichiers JSON (`lang/fr.json`), pas des dossiers PHP.
- Ne jamais mettre dans `lang/en.json` une clé contenant `$`, `{{` ou `}}` : ce sont des chaînes interpolées par Blade, non traduisibles.
- Les textes d'interface doivent être extractionnables : pas de concaténation (`__('Edit ') . $name`), sinon la chaîne n'arrive jamais dans le catalogue.
- `lang/en/*.php` (auth, pagination, passwords, validation) et `lang/vendor/**` sont des fichiers du framework : hors périmètre de la traduction.
- Les tests rejouent migrations + seeders à chaque test : une suite de quelques tests prend plusieurs minutes, lancer un fichier ciblé.
- Un fichier `tests/Feature` met 2 à 4 minutes : regrouper plusieurs fichiers dans un seul `lerd artisan test --compact a.php b.php` au lieu de relancer à chaque édition.
- Le helper de test maison est `App\Traits\FeatureTestTrait` (`workingSchool()`, `memberOf($school, $user)`, `authorized_user([...])`) — il n'est pas dans `tests/`.
- Une nouvelle permission exige **une migration de backfill** en plus de `database/seeders/PermissionSeeder.php` (qui la déclare à trois endroits) : le seeder ne rejoue pas sur une install existante et l'écran resterait fermé. Voir `.ai/rules/migrations.md`.
- Un test qui touche un payload JSON compare avec `assertEquals` : les floats ressortent en `int` (12 et non 12.0).
