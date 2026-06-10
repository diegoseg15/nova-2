# Arquitectura modular PHP - NOVA 2

## Objetivo

Organizar NOVA como un monolito modular PHP mantenible, separando configuración, bootstrap, helpers, servicios, vistas y módulos funcionales.

## Principios

- Los archivos raíz deben actuar como puntos de entrada.
- La lógica de negocio debe vivir en services.
- La configuración debe centralizarse en config.
- Las variables sensibles deben venir de entorno, no del código.
- Los módulos deben agrupar funcionalidad por dominio.
- Los helpers deben contener funciones pequeñas y transversales.
- Las vistas no deben contener consultas SQL ni reglas de negocio.

## Capas

```txt
HTTP Entry Point
  → app/core/bootstrap.php
  → config/*
  → app/services/*
  → app/helpers/*
  → app/views/*
  → modules/*
```
