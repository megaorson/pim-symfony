# PIM Symfony Project

A simplified Product Information Management (PIM) system inspired by Akeneo and Magento.

This project demonstrates a scalable backend architecture using a dynamic attribute model (EAV) and is designed to evolve into an API-first system.

---

## 🚀 Overview

This system provides a centralized platform for managing product data with a flexible schema.

Unlike traditional applications with fixed database columns, this project supports dynamic attributes, allowing products to have different structures without schema changes.

---

## 🧬 Core Concept

Products are built using a dynamic attribute system:

- Custom attributes (color, size, material, etc.)
- Multiple data types (text, number, image, etc.)
- Extendable without database migrations

---

## 🧠 Data Model (EAV Pattern)

The system uses an Entity-Attribute-Value (EAV) architecture inspired by Magento.

### Core Entities

- Product — main entity (SKU)
- ProductAttribute — attribute definition (code, name, type)

### Value Storage (Type-based)

Values are stored in separate tables depending on type:

- ProductAttributeValueText
- ProductAttributeValueDecimal
- ProductAttributeValueInt
- ProductAttributeValueImage

### Example

Product: T-Shirt

Attributes:
- name (text)
- price (decimal)
- stock (int)
- image (image)

Storage:

- ProductAttributeValueText → name
- ProductAttributeValueDecimal → price
- ProductAttributeValueInt → stock
- ProductAttributeValueImage → image

### Benefits

- Flexible schema (no migrations required)
- Strong typing
- Scalable for large datasets
- Industry-standard approach

### Trade-offs

- More complex queries (JOIN-heavy)
- Harder filtering
- Requires careful indexing

---

## ✨ Features

- Authentication (Symfony Security)
- Admin panel (EasyAdmin)
- Dynamic product attributes
- Type-based value storage
- Scalable architecture
- Ready for API integration

---

## 🧱 Tech Stack

- PHP 8+
- Symfony
- Doctrine ORM
- EasyAdmin
- Twig
- Tailwind CSS

---

## 📡 API (Planned)

Endpoints:

- GET /api/products
- GET /api/products/{id}

Example:

{
"sku": "t-shirt",
"attributes": {
"name": "Basic T-Shirt",
"price": 29.99,
"stock": 100
}
}

Authentication: JWT (planned)

---

## 🔮 Roadmap

- REST API
- JWT authentication
- Attribute groups/families
- Advanced filtering
- External PIM integrations
- Caching layer

---

## 📊 Architecture Highlights

- EAV data modeling
- Separation of concerns
- Scalable backend design
- Admin-driven data management

---

## ⚙️ Installation

composer install
symfony server:start

---

## 🔐 Admin Setup

php bin/console app:create-admin email password

---

## 💬 Summary

This project demonstrates:

- Real-world EAV implementation
- Backend architecture design
- Admin UI integration
- Preparation for scalable API systems

---

## 📄 License

MIT
