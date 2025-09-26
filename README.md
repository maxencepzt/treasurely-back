# Treasurely

## Auteurs :

- Maxence POIZAT
- Ylan NICOLAS
- Clément DAVID
- Jules DESCOUTURES

## Installation / Configuration

### Cloner le projet

```bash
git clone https://iut-info.univ-reims.fr/gitlab/treasurely/sae5-01-back.git
cd sae5-01-back
```

### Installation

Ce projet utilise Symfony. Pour l'installation :

#### Installer les dépendances PHP


```bash
composer install
```

#### Pour réinitialiser la base de donnée.

```bash
composer db
```

### Documentation des commandes

- `fix:csfixer` : Corrige le code PHP avec PHP CS Fixer
- `fix:twig` : Corrige le code PHP avec Twig CS Fixer
- `test:csfixer` : Teste le code PHP avec PHP CS Fixer
- `test:phpstan` : Teste le code PHP avec PhpStan
- `test:twig` : Teste le code PHP avec Twig CS Fixer
- `test:yaml` : Vérifie les fichiers dans le répertoire config
- `test` : Lance tous les tests
- `fix` : Lances tous les scripts de correction
- `db` : Supprime, crée, migre et charge les fixtures dans la base de données  
