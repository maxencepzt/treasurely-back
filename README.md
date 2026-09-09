# Treasurely

Treasurely est une application de chasses au trésor. Des concepteurs écrivent des chasses faites
d'énigmes ordonnées, des joueurs les rejoignent, seuls ou en équipe, et les résolvent sur le
terrain, contre la montre : plus on répond vite, plus l'énigme rapporte.

Ce dépôt est le back : l'API consommée par l'application des joueurs, la façade des concepteurs
et le back office. L'application des joueurs vit dans
[treasurely-front](https://github.com/maxencepzt/treasurely-front).

- Application en ligne : https://treasurely.maxencepzt.fr
- API : https://api.treasurely.maxencepzt.fr (documentation sur `/api/docs`)

## Comment ça se joue

1. **Une équipe.** Un joueur crée une équipe de joueurs et reçoit un code (`treasurely_` suivi
   de treize chiffres) ; ses amis le saisissent pour la rejoindre, ou demandent à entrer depuis
   l'annuaire, et le créateur accepte ou refuse.
2. **Une chasse.** Les chasses ouvertes sont listées avec leur ville, leur durée estimée et leur
   difficulté (1 à 3). On y participe seul ou pour une de ses équipes.
3. **Des énigmes, dans l'ordre.** Chaque énigme s'ouvre après la précédente. Quatre formes :
   - **texte** : une réponse à taper, comparée sans tenir compte de la casse, des accents ni des
     espaces ;
   - **QCM** : une ou plusieurs bonnes réponses parmi les choix ;
   - **QR code** : un code imprimé sur place, `treasurely_` suivi de neuf chiffres, attribué par
     le serveur et jamais modifié ;
   - **GPS** : se trouver à moins de cinquante mètres du point.
4. **Le score.** Le chronomètre d'une énigme démarre quand le joueur l'affiche. Une bonne réponse
   vaut `difficulté × 1000 × e^(-0,001 × secondes)`, donc jusqu'à 1 000, 2 000 ou 3 000 points, et
   fond avec le temps. Au-delà du nombre d'essais notés fixé par le concepteur, une réussite
   compte encore, mais pour zéro point. Le serveur arbitre tout : la solution ne quitte jamais
   la base.
5. **Le classement.** Chaque chasse a son tableau des dix meilleurs finisseurs ; un joueur qui a
   fini voit une fenêtre autour de sa propre place. Une chasse terminée peut être rejouée, le
   nouveau score remplace l'ancien ; une chasse commencée peut être quittée.

Les concepteurs ne jouent pas leurs propres chasses.

## Trois surfaces, une application Symfony

| Surface | Chemin | Authentification |
|---|---|---|
| API JSON-LD (API Platform), consommée par l'application des joueurs | `/api` | JWT sans état (`POST /api/auth` avec `nickname` et `password`, jetons de rafraîchissement sur `/api/token/refresh`) |
| Façade des concepteurs (Twig, Tailwind) : équipes conceptrices, chasses, énigmes, QR imprimables, statistiques | `/designer` | session, ouverte depuis l'application par échange du JWT (`POST /sso/login`) |
| Back office (EasyAdmin) : consultation, correction et suppression | `/admin` | session, `ROLE_ADMIN` |

Les règles du jeu vivent côté serveur : les opérations de l'API sont déclarées sur les entités
Doctrine (`src/Entity`), la logique dans `src/State` (rejoindre, répondre, rejouer, quitter,
demandes d'adhésion) et `src/Service` (score, édition des chasses). Le cycle de vie d'une chasse
(`draft`, `opened`, `closed`) est un workflow Symfony. Les statistiques d'un joueur sont des
agrégats recalculés par des écouteurs Doctrine.

## Pile technique

PHP 8.4, Symfony 7.3, API Platform 4.2, Doctrine ORM, PostgreSQL 16 (Postgres 17 en production),
Lexik JWT, EasyAdmin, Twig avec Tailwind (`symfonycasts/tailwind-bundle`), Codeception pour les
tests, PHPStan (niveau 6), PHP CS Fixer, GrumPHP en pré-commit. En production, FrankenPHP dans un
conteneur.

## Démarrer en local

Prérequis : PHP 8.4, Composer, Docker (pour PostgreSQL), le binaire `symfony`.

```bash
git clone git@github.com:maxencepzt/treasurely-back.git
cd treasurely-back
composer install

# Secrets locaux, jamais versionnés (.env.local est ignoré par git)
printf 'APP_SECRET=%s\nJWT_PASSPHRASE=%s\n' "$(openssl rand -hex 16)" "$(openssl rand -hex 32)" > .env.local

composer jwt:generate            # clés JWT dans config/jwt, avec la passphrase ci-dessus
docker compose up -d database    # PostgreSQL sur :5432 (Adminer sur :7080, Mailpit sur :8025)
composer db                      # base recréée, migrée, fixtures de développement (données Faker)
composer start                   # http://localhost:8000
```

Pour une base propre avec le jeu de démonstration plutôt que les données Faker :

```bash
bin/console doctrine:database:drop --force && bin/console doctrine:database:create
bin/console doctrine:migrations:migrate -n
bin/console doctrine:fixtures:load --group=demo --append -n
```

Le groupe `demo` (`src/DataFixtures/DemoFixtures.php`) crée un administrateur (`maxence`),
quatre joueurs (`camille`, `theo`, `ines`, `sacha`), une équipe conceptrice, deux équipes de
joueurs et deux chasses ouvertes dans Paris, construites par le même service que la façade.
Tous ces comptes ont le mot de passe temporaire défini dans `DemoFixtures::PASSWORD`, à changer
dès la première connexion sur une base qui n'est pas jetable.

Le `.env` versionné ne contient que des valeurs de remplacement. Les vraies valeurs vont dans
`.env.local` en développement et dans l'environnement du serveur en production.

## Scripts Composer

| Script | Effet |
|---|---|
| `composer start` | serveur de développement (`symfony serve`) |
| `composer test` | PHP CS Fixer, PHPStan, Twig CS Fixer, YAML, puis Codeception |
| `composer test:codeception` | recrée la base de test (SQLite) et lance les suites `Api` et `Unit` |
| `composer test:codeception_w` | la même chose sous Windows |
| `composer test:phpstan`, `test:csfixer`, `test:twig`, `test:yaml` | une étape à la fois |
| `composer fix` | PHP CS Fixer et Twig CS Fixer, en place |
| `composer db` | supprime, crée, migre et charge les fixtures de développement |
| `composer jwt:generate` | génère la paire de clés JWT |
| `composer openapi:generate` | exporte la documentation OpenAPI dans `public/openapi.json` |

Un test seul, une fois la base de test créée par `composer test:codeception` :

```bash
php vendor/bin/codecept run tests/Api/Riddle/RiddleAttemptCest.php
```

## Tests

Codeception, suite `Api` (requêtes HTTP à travers le noyau, base SQLite, transaction annulée
après chaque test) et suite `Unit`. Les données viennent de factories Zenstruck Foundry
(`src/Factory`). Le back office est testé sur le pare-feu de session (`tests/Api/Admin`), la
façade des concepteurs dans `tests/Api/Designer`, le jeu dans `tests/Api/Riddle` et
`tests/Api/ParticipateHunt`. La suite a sa propre paire de clés JWT, générée à la volée sous
`var/jwt/test/`. Chaque fonctionnalité et chaque correctif arrivent avec leurs tests, dans la
même pull request.

## Déploiement

- **Conteneur unique** (`Dockerfile.vercel`, `docker/frankenphp/Caddyfile`) : FrankenPHP sert
  l'API, la façade et le back office sur le port injecté dans `PORT`. Vercel construit et
  déploie ce conteneur à chaque mise à jour de `main`, dans la région de Paris (`vercel.json`).
- **Tout vient de l'environnement** : `DATABASE_URL`, `APP_SECRET`, `JWT_PASSPHRASE`,
  `JWT_SECRET_KEY` et `JWT_PUBLIC_KEY` en contenu PEM, `CORS_ALLOW_ORIGIN`, `FRONTEND_URL`.
- **Base de données** sur Supabase, jointe par son session pooler (le conteneur n'a pas d'IPv6
  sortant). Les migrations et les fixtures se lancent depuis un poste de travail avec la
  `DATABASE_URL` de production exportée, jamais au démarrage du conteneur.
- **Sessions en base** (`PdoSessionHandler`) : plusieurs instances du conteneur servent les
  requêtes successives, un fichier de session ne les suivrait pas.
- Le `Dockerfile` historique (php-fpm et nginx, services `prod` de `docker-compose.yaml`) reste
  utilisable pour un hébergement classique.

## Conventions

Branches `<type>/<description-courte>`, commits Conventional Commits en anglais, une pull request
documentée par branche, fusionnée avec un commit de fusion. Le détail est dans
[CONTRIBUTING.md](CONTRIBUTING.md). GrumPHP relance la pipeline avant chaque commit et refuse
les `dump()` oubliés.

## Auteurs

Maxence Poizat, Ylan Nicolas, Clément David, Jules Descoutures. Projet né à l'IUT de Reims
(SAE 5.01), poursuivi et mis en ligne par Maxence Poizat.
