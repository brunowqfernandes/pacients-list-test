# Cadastro de Pacientes

Aplicação Fullstack para gerenciamento de pacientes, desenvolvida como parte do desafio técnico para a vaga de Fullstack Developer.

O projeto disponibiliza uma API REST desenvolvida em **PHP/Laravel** e uma interface web desenvolvida em **Vue.js 2**, permitindo cadastrar, consultar, editar, excluir e restaurar pacientes.

## Tecnologias utilizadas

### Backend
- PHP
- Laravel
- MySQL
- Docker e Docker Compose
- PHPUnit

### Frontend
- Vue.js 2
- TypeScript
- Vue Router
- BootstrapVue
- Vite
- CSS com metodologia BEM

## Estrutura do projeto

```text
.
├── php-laravel/       # API REST e aplicação Laravel
├── front-vuejs/       # Aplicação frontend Vue.js
├── README.md
└── ...
```

## Funcionalidades

- Cadastro de pacientes.
- Listagem de pacientes com paginação.
- Pesquisa de pacientes por nome ou CPF.
- Visualização e edição dos dados do paciente.
- Validação dos campos obrigatórios.
- Formatação de CPF, RG e telefone na interface.
- Exclusão de pacientes com confirmação.
- Exclusão lógica (Soft Delete), preservando os registros no banco de dados.
- Listagem de pacientes excluídos.
- Restauração de pacientes excluídos.

### Dados do paciente

**Campos obrigatórios:**
- Nome completo
- CPF
- RG
- Data de nascimento
- Sexo biológico
- Celular

**Campos opcionais:**
- E-mail
- Descrição

## Pré-requisitos

Antes de iniciar, certifique-se de ter instalado:

- Docker
- Docker Compose
- Node.js
- npm

## Como executar o projeto

O backend e o frontend são aplicações independentes e devem ser executados simultaneamente.

### 1. Backend — Laravel

### Pré-requisitos

- Docker Desktop com Docker Compose
- PHP 8.2
- Composer

### Instalação

Acesse a pasta do backend:

```bash
cd php-laravel
```

Copie o arquivo de ambiente:

```bash
cp .env.example .env
```

> No Windows PowerShell, você também pode utilizar:
> `Copy-Item .env.example .env`

Instale as dependências do PHP:

```bash
composer install
```

Suba os containers:

```bash
docker compose up -d --build
```

Gere a chave da aplicação:

```bash
docker compose exec laravel.test php artisan key:generate
```

Execute as migrations:

```bash
docker compose exec laravel.test php artisan migrate
```

Popule o banco com os dados de exemplo:

```bash
docker compose exec laravel.test php artisan db:seed
```

A API ficará disponível em:

`http://localhost:8000/api`

### Executando os testes

```bash
docker compose exec laravel.test php artisan test
```

### Encerrando o ambiente

```bash
docker compose down
```

Os dados do banco são mantidos no volume Docker `sail-mysql`.


### 2. Frontend — Vue.js

### Pré-requisitos

- Node.js
- npm

### Instalação

Acesse a pasta do frontend:

```bash
cd front-vuejs
```

Instale as dependências:

```bash
npm install
```

Crie o arquivo `.env` na raiz de `front-vuejs` com o seguinte conteúdo:

```dotenv
VITE_API_BASE_URL=http://localhost:8000/api
```

Inicie o servidor de desenvolvimento:

```bash
npm run dev
```

Acesse a aplicação pelo endereço exibido no terminal, normalmente:

`http://localhost:5173`

### Validação de produção

Para verificar os tipos e gerar o build:

```bash
npm run build
```


## Testes automatizados

Os testes da API podem ser executados a partir da pasta `php-laravel`:

```bash
docker compose exec laravel.test php artisan test
```

A suíte contempla os principais comportamentos da API, incluindo:

- Cadastro de pacientes.
- Consulta e listagem.
- Atualização de dados.
- Validação de informações.
- Exclusão lógica.
- Consulta de pacientes excluídos.
- Restauração de registros.

## API REST

Base URL:

```text
http://localhost:8000/api
```

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/patients` | Lista pacientes ativos |
| POST | `/patients` | Cadastra um paciente |
| GET | `/patients/{id}` | Consulta um paciente |
| PUT | `/patients/{id}` | Atualiza um paciente |
| DELETE | `/patients/{id}` | Exclui logicamente um paciente |
| GET | `/patients/trashed` | Lista pacientes excluídos |
| POST | `/patients/{id}/restore` | Restaura um paciente excluído |

### Exemplo de cadastro

```json
{
  "name": "Maria da Silva",
  "cpf": "12345678901",
  "rg": "123456789",
  "birth_date": "1990-05-20",
  "biological_sex": "F",
  "cellphone": "11999999999",
  "email": "maria@example.com",
  "description": "Paciente de demonstração"
}
```

### Observações

- Os endpoints de cadastro e atualização validam os campos obrigatórios.
- O CPF deve ser único.
- A exclusão utiliza Soft Delete, mantendo o registro no banco de dados.
- Pacientes excluídos não aparecem na listagem convencional.
- Pacientes excluídos podem ser consultados e restaurados pelos endpoints específicos.

## Coleção Postman

A coleção Postman com exemplos de requisições da API está disponível em:

```text
postman/bi-tech-interview.postman_collection.json
```

Importe o arquivo no Postman para executar e testar os endpoints da aplicação.

## Considerações finais

O projeto foi desenvolvido com foco em organização, separação de responsabilidades, validação de dados e boas práticas de desenvolvimento, contemplando os requisitos funcionais propostos no desafio técnico.