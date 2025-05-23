# 🗳️ Simple Poll - Drupal 10

Este é um projeto customizado de sistema de votação (poll) desenvolvido em **Drupal 10**, com suporte a CRUD completo, exibição pública e integração via REST API.

## ✅ Requisitos

- [Docker](https://www.docker.com/)
- [Lando](https://lando.dev/) (ambiente local baseado em containers)

---

## 🚀 Como rodar o projeto localmente

### 1. Clonar o repositório

```bash
git clone git@github.com:ricardovianasi/simple-poll.git
cd simple-poll
```

### 2. Iniciar o ambiente com Lando

```bash
lando start
```

> Isso criará os containers com Apache + PHP + MariaDB + Drush.

### 3. Instalar dependências via Composer

```bash
lando composer install
```

### 4. Importar o banco de dados

Certifique-se de que o dump esteja presente em `database/dumps/dump.sql`. Então execute:

### 5. Acessar o site

Abra o navegador em:

```
http://simple-poll-lando.lndo.site/
```

---

## 🔐 Acesso administrativo

Use `drush uli` para gerar um link de login automático:

```bash
lando drush uli
```

---

## 📁 Estrutura do projeto

```
simple-poll/
├── .lando.yml            # Configuração do ambiente local
├── web/                  # Raiz do site Drupal
├── db/dump.sql.gz        # Dump do banco de dados
├── composer.json         # Dependências
├── modules/custom/simple_poll/  # Módulo customizado de votação
└── README.md
```

---

## 🔁 API REST

- GET `/api/simple-poll/{identifier}`
- POST `/api/simple-poll/{identifier}/vote`

Autenticação via cookie ou token. Veja o plugin `SimplePollResource`.

---

## 📌 Observações

- Certifique-se de que `simple_poll` esteja habilitado:
  ```bash
  lando drush en simple_poll -y
  ```
- Você pode customizar o domínio alterando `proxy:` no `.lando.yml`.

---

Feito com 💙 usando Drupal 10 + Lando.
