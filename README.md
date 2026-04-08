# PIM Symfony (EAV + API Platform)

A Product Information Management (PIM) system built with **Symfony + API Platform + EAV architecture**.

This project demonstrates a **scalable, API-first backend** with:

- dynamic attributes (EAV)
- custom filtering DSL
- field selection (`select`)
- multi-field sorting (`sort`)
- pagination
- DTO + Provider/Processor architecture
- functional API test coverage
- clean modular architecture

---

## 🚀 Overview

This system provides a centralized platform for managing product data with a flexible schema.

Unlike traditional systems with fixed columns, this project uses a dynamic attribute model (EAV), allowing products to have different structures without database changes.

The system is designed with an **API-first approach**, where all business logic is exposed via REST endpoints and documented via OpenAPI.

In addition to the API design itself, the project now includes a growing **functional test suite** for both attribute and product APIs, covering request/response contracts, validation behavior, query features, and persistence side effects.

---

## 🧬 Core Concept

Products are built using a dynamic attribute system:

- Custom attributes (color, size, material, price, etc.)
- Multiple data types (text, decimal, int, image)
- No schema changes required for new attributes
- Fully queryable via filter, select, sort, and pagination

The external API representation is intentionally different from the internal persistence model:

- `id` stays at the root level
- `sku` is stored as a system field in the entity
- API responses expose `sku` inside `attributes`
- EAV values are stored in type-specific tables

This keeps the public contract consistent while still allowing an efficient persistence model.

---

## 🧠 Filter DSL Design (Parser Architecture)

The filtering system is implemented as a custom **domain-specific language (DSL)**.

The architecture follows a classic flow:

`string → tokens → AST → execution`

This approach is inspired by expression parsing techniques described in the book *The C++ Programming Language* by Bjarne Stroustrup, where expressions are parsed with recursive descent and evaluated via a syntax tree.

### In this project

The same idea is applied to filtering:

```text
(price GT 1000 OR name EQ 'Phone') AND qty GE 1
```

is processed as:

`string → tokens → AST → Doctrine QueryBuilder`

### Components

- **Tokenizer**
    - Converts string into tokens (IDENTIFIER, OPERATOR, VALUE, AND, OR, LPAREN, RPAREN, etc.)

- **Parser**
    - Builds an AST using recursive descent parsing

- **AST**
    - `ConditionNode` — single condition
    - `GroupNode` — logical grouping (`AND` / `OR`)

- **SmartEavFilterApplier**
    - Traverses AST and builds Doctrine QueryBuilder

### Supported Operators

- `EQ`
- `NE`
- `GT`
- `GE`
- `LT`
- `LE`
- `IN`
- `BEGINS`

### Supported Logic

- `AND`
- `OR`
- parentheses / nested groups

### Why this approach

- Supports complex expressions
- Separates parsing from execution
- Extensible for new operators and functions
- Enables clean validation and predictable `400` responses instead of server errors

---

## 🧠 Data Model (EAV Pattern)

### Core Entities

- **Product** — base entity (`id`, `sku`, `createdAt`, `updatedAt`)
- **ProductAttribute** — attribute definition (`code`, `type`)

### Value Storage (Type-based)

Values are stored in separate tables depending on type:

- `ProductAttributeValueText`
- `ProductAttributeValueDecimal`
- `ProductAttributeValueInt`
- `ProductAttributeValueImage`

This allows strongly typed storage while preserving the flexibility of an EAV model.

---

## ⚡ Query Capabilities

### Filtering (DSL)

```http
GET /api/products?filter=price GT 1000
GET /api/products?filter=name BEGINS 'A'
GET /api/products?filter=price GT 1000 OR price LT 10
GET /api/products?filter=(name EQ 'Alpha' OR name EQ 'Beta') AND qty GE 1
GET /api/products?filter=sku EQ 'SKU-001'
GET /api/products?filter=name IN ('One','Three')
```

Features:

- AST-based parsing
- works with system + EAV fields
- `AND` / `OR` / parentheses
- converts DSL → Doctrine QueryBuilder

### Sorting

```http
?sort=price
?sort=-price
?sort=price,sku
?sort=sku,-price
```

Rules:

- left-to-right priority
- first field = highest priority
- next fields = tie-breakers
- supports system + EAV fields
- stable sorting via fallback
- null-safe ordering strategy

### Field Selection

```http
?select=sku,name,price
```

Rules:

- reduces payload size
- `id` stays in root
- selected fields are returned inside `attributes`
- works on collection responses

### Pagination

```http
?page=1&limit=20
```

Internally converted to offset-based pagination.

---

## 📡 API

### Endpoints

```http
GET    /api/products
GET    /api/products/{id}
POST   /api/products
PATCH  /api/products/{id}
DELETE /api/products/{id}
```

---

## 📦 Example Response

### Collection Response

```json
{
  "items": [
    {
      "id": 1,
      "attributes": {
        "sku": "SKU-001",
        "name": "Phone",
        "price": 1200
      }
    }
  ],
  "total": 57,
  "limit": 20,
  "offset": 0
}
```

### Item Response

```json
{
  "id": 1,
  "attributes": {
    "sku": "SKU-001",
    "name": "Phone",
    "price": 1200,
    "qty": 5
  }
}
```

---

## 🧱 Architecture

```text
Request
  ↓
DTO / Input
  ↓
Processor / Provider
  ↓
Domain Services
  ↓
Doctrine / QueryBuilder
  ↓
DTO / Output
  ↓
API Response
```

### Collection Flow

```text
Request
  ↓
ProductCollectionContextFactory
  ↓
ProductCollectionContext
  ↓
Collection Appliers
  ├── ProductFilterApplier
  ├── ProductSelectApplier
  └── ProductSortApplier
  ↓
Doctrine QueryBuilder
  ↓
ProductCollectionProvider
  ↓
ResultMapper
  ↓
API Response
```

### DSL → SQL Flow

```text
Filter string
    ↓
Tokenizer
    ↓
Parser
    ↓
AST
    ↓
SmartEavFilterApplier
    ↓
Doctrine QueryBuilder
    ↓
SQL
```

---

## 🧩 Field System (Key Architecture)

- **ProductSystemFieldRegistry** → system fields
- **AttributeMetadataProvider** → EAV metadata
- **ProductCollectionFieldProvider** → unified API field model

This eliminates duplication and keeps:

- validation
- documentation
- runtime logic

fully consistent.

---

## ✅ Functional Testing

The project includes a dedicated **functional API testing foundation** based on `ApiTestCase`.

### Test Foundation

- isolated database cleanup before each test
- reusable JSON request helpers
- reusable API assertion helpers
- attribute/product test factory helpers
- dedicated `ProductApiTestCase`
- response contract assertions
- persistence-side assertions for product attributes

### Covered Product Test Areas

#### Create

- create product with required attributes
- create product with optional attributes
- fail when required attribute is missing
- fail when `sku` is missing
- fail when unknown attribute is provided

#### Get Item

- return product by id
- return optional attribute when present
- return `404` when product does not exist

#### Get Collection

- empty collection response
- collection with created products
- pagination behavior
- collection sorting behavior

#### Patch

- update existing attributes
- add optional attribute
- keep unchanged attributes intact
- remove optional attribute with `null`
- fail when required attribute becomes `null`
- fail on unknown attribute
- return `404` for missing product
- validate persisted DB state after patch

#### Filter DSL

Functional coverage includes:

- `EQ`
- `NE`
- `GT`
- `GE`
- `LT`
- `LE`
- `IN`
- `BEGINS`
- `AND`
- `OR`
- parentheses / nested groups
- system fields like `sku`
- invalid field / invalid syntax cases

#### Select

Collection-level select coverage includes:

- selecting only requested fields
- selecting system + EAV fields together
- selecting a single field
- combining select with pagination
- validation for unknown selected fields

### Testing Philosophy

The functional test suite validates both:

- **response contract** — status codes, JSON shape, selected fields, query behavior
- **persistence side effects** — correct stored values in the product aggregate after create/patch

This is especially important for EAV systems, where a response can look correct while persistence logic is broken.

---

## ⚡ Performance Considerations

Current strategy:

- joins for EAV filtering/sorting/select
- hidden fields for sorting
- optimized collection mapping

Trade-offs:

- complex joins for EAV
- heavier queries under large datasets

Planned improvements:

- 2-step pagination (IDs → entities)
- metadata caching
- indexing strategy
- large dataset benchmarks

---

## ✨ Features

- Dynamic EAV attributes
- Custom DSL filtering engine
- Select / Sort / Pagination
- DTO-based API
- Provider / Processor architecture
- OpenAPI / Swagger docs
- EasyAdmin backend
- Functional API tests
- Translations support

---

## ⚠️ Trade-offs

- Complex SQL queries
- Requires indexing strategy
- EAV complexity under high load
- More domain/application code than fixed-schema CRUD

---

## 🔮 Roadmap

- Production-grade pagination (2-step)
- Attribute capabilities hardening (`filterable`, `sortable`, `selectable`)
- Attribute groups / families
- Multi-tenant support
- Search integration (Elastic/OpenSearch)
- Large-scale benchmark dataset
- More write-side validation scenarios

---

## 🧱 Tech Stack

- PHP 8+
- Symfony
- API Platform
- Doctrine ORM
- MySQL
- EasyAdmin
- OpenAPI / Swagger
- PHPUnit

---

## ⚙️ Installation

```bash
composer install
```

---

## 🔐 Admin Setup

```bash
php bin/console app:create-admin email password
```

---

## 💬 Summary

This project demonstrates:

- advanced backend architecture
- EAV modeling
- custom query language (DSL)
- API-first design
- DTO + Provider/Processor patterns
- functional API testing strategy
- scalable and extensible system design

---

## 📄 License

MIT
