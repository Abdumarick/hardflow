# 01 Project Overview

## Product vision
Build a reliable Hardware Business Management System for businesses selling both wholesale and retail. Typical inventory includes cement, iron bars/nondo, roofing sheets, nails, pipes, paint, electrical materials, tools, plumbing materials, blocks, tiles, sinks, water installation materials, and related products. A business may hold 10,000+ SKUs.

The platform must be designed for multiple independent businesses. A business may later operate multiple branches. One owner may own multiple businesses. A user may, with explicit permission, work across multiple branches or businesses.

## Hierarchy
Platform -> Business -> Branch -> Users and Transactions.

Super Admin operates at platform level. Shop Owner operates at business level. Business roles include Manager, Cashier, Storekeeper, Accountant and Salesperson, with support for custom roles.

## Core business characteristics
Products may have multiple units and conversions, such as box to kilograms or roll to metres. Buying prices change over time. Selling prices support retail, wholesale and configurable/special price levels. Authorized price overrides must be logged.

Purchases require receiving and confirmation before stock is updated. Sales, payments and physical goods release are separate concepts so credit sales and partial goods collection can be represented correctly.

The platform requires strong accountability: permissions, approval levels, audit trails, transaction history and controlled stock adjustments.

## Language and devices
The application should support English and Kiswahili. It should be responsive for desktop, tablet and smartphone, with POS/touch-screen considerations. A4, thermal receipt and future EFT/POS printer support are required.

## Currency
TZS is the default currency. The data model should be prepared for multi-currency operation.
