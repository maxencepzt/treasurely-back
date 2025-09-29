# Conventions Git

Ce document décrit les conventions de nommage des branches et de rédaction des commits utilisées dans ce projet.

---

## 🌱 Branches

### Format
`<type>/<description-courte>`  
ou  
`<type>/<id-ticket>-<description-courte>`

### Types de branches
- `feature/` → nouvelle fonctionnalité
- `fix/` → correction de bug
- `hotfix/` → correction urgente en production
- `release/` → préparation d'une version stable
- `chore/` → tâches techniques (maintenance, config, refactoring)
- `test/` → expérimentations ou POC

### Règles
- Utiliser uniquement des **lettres minuscules** et des **tirets (-)** pour séparer les mots.
- Nom explicite mais concis (pas plus de 5-6 mots).
- Inclure l’identifiant du ticket si applicable.

### Exemples
- `feature/add-user-authentication`
- `fix/user-profile-picture`
- `hotfix/payment-crash`
- `release/v1.2.0`
- `chore/update-eslint-config`
- `feature/PROJ-123-export-data`

---

## 📝 Commits

### Format
`<type>(scope): message court à l’infinitif`

### Types de commits (inspirés de Conventional Commits)
- `feat` → ajout d’une nouvelle fonctionnalité
- `fix` → correction d’un bug
- `docs` → modification de la documentation
- `style` → changements de style/formatage (pas de code fonctionnel)
- `refactor` → refactorisation sans changement de fonctionnalité
- `test` → ajout ou modification de tests
- `chore` → maintenance, CI/CD, dépendances, outils

### Règles
- Si besoin, ajouter une description plus détaillée après une ligne vide.
- Toujours un commit par modification logique (éviter les commits fourre-tout).

### Exemples
- `feat(auth): add Google login`
- `fix(api): fix JSON response format`
- `docs(readme): update installation section`
- `style(css): unify heading font sizes`
- `refactor(user): simplify profile validation`
- `test(order): add unit tests for validation`
- `chore(deps): update axios to v1.6.0`

---

## 🔄 Bonnes pratiques générales
- Ne jamais travailler directement sur `main`.
- Toujours passer par une branche dérivée.
- Supprimer les branches une fois mergées pour garder un dépôt propre.
- Faire des commits petits, clairs et logiques.

---
