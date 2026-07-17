# AuthNexora

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/status-active-blue.svg)]()

**AuthNexora** é uma API de autenticação robusta e modular desenvolvida em PHP 8.2+ e MySQL. Projetada para ser o coração de sistemas que exigem segurança e escalabilidade, ela oferece fluxos completos de login, registro, recuperação de conta e segurança JWT, tudo documentado com OpenAPI (Swagger).

## 🚀 Visão Geral

Este projeto foi construído com foco em arquitetura limpa e facilidade de integração. As principais responsabilidades incluem:
- Gestão de identidades e usuários.
- Autenticação segura via JSON Web Tokens (JWT).
- Fluxo de recuperação de senha com integração via PHPMailer.
- Autenticação com Google OAuth.
- Autenticação de dois fatores (2FA).
- Documentação interativa para desenvolvedores.

## 🐳 Docker (Infraestrutura)

> **A infraestrutura Docker deste projeto foi centralizada no repositório [CICD](https://github.com/ReiArthurjpg/CICD).**
>
> Para rodar via Docker, use o `docker-compose.yml` do repositório CICD.
> Este projeto contém apenas o `Dockerfile` para build da aplicação.

## 💻 Desenvolvimento Local (XAMPP)

1. Clone/copiar projeto para `htdocs` do XAMPP.
2. No terminal:
   ```bash
   cd /caminho/para/htdocs/AuthNexora/api
   composer install
   ```
3. Ajuste `api/.env` com credenciais locais.
4. Importe o schema do banco manualmente usando o arquivo disponível no repositório CICD:
   - `CICD/database/schemas/authnexora/001_initial_schema.sql`
   - `CICD/database/seeds/authnexora/001_admin_user.sql`

## 📖 Documentação Detalhada

Para instruções completas de instalação, guias técnicos, arquitetura do banco de dados e referências dos endpoints, visite nossa Wiki oficial:

👉 [**Acessar Wiki do AuthNexora**](https://github.com/ReiArthurjpg/AuthNexora/wiki)

---
<p align="center">
  Desenvolvido por <a href="https://github.com/ReiArthurjpg">ReiArthurjpg</a>
</p>
