
---
name: generar-request-laravel
description: Genera Form Requests de Laravel para Kanri siguiendo las convenciones existentes del proyecto. Utiliza esta habilidad cuando el usuario solicite crear, completar o ajustar Request para métodos como all, store, update, settings, delete o restore. Todas las explicaciones, resúmenes de cambios, advertencias y preguntas dirigidas al usuario deben estar en español. Conserva en inglés los nombres de clases, métodos, propiedades, variables y términos técnicos que deban coincidir con el código del proyecto. Los comentarios nuevos en el código también deben estar en español, salvo que las convenciones existentes indiquen lo contrario.
---

# Generar Form Requests en Kanri

## Objetivo

Crear Form Requests de Laravel que respeten la arquitectura, estructura y convenciones existentes en el backend Kanri.

## Procedimiento obligatorio

Cuando el usuario solicite generar un Request:

1. Identifica el modelo sobre el cual se trabajará.
2. Revisa el modelo para conocer sus campos, relaciones y restricciones.
3. Busca los Request existentes del mismo modelo.
4. Si no existen, revisa Request de modelos similares para identificar las convenciones del proyecto.
5. Identifica el método del controlador al que corresponde el Request: all, store, update, destroy u otro.
6. Genera únicamente el Request solicitado, siguiendo las convenciones encontradas.
7. Si el usuario ya tiene un Request parcialmente implementado, conserva su estructura y completa lo necesario.

## Reglas de validación

- No inventes campos, columnas ni relaciones.
- No asumas que todos los campos del modelo son obligatorios.
- No copies reglas de otro modelo sin verificar que correspondan.
- Utiliza los tipos de validación adecuados para los datos esperados.
- Respeta las convenciones de namespaces, nombres de clases y organización de carpetas.
- Conserva la estructura de autorización y mensajes personalizados que utilice el proyecto.
- Si una regla depende de información que no está disponible, pregunta al usuario antes de asumirla.

## Alcance de los cambios

- No modifiques modelos, controladores, migraciones, rutas ni otros archivos si el usuario no lo solicita.
- No sobrescribas un Request existente sin revisar primero su contenido.
- No ejecutes migraciones ni alteres la base de datos.

## Resultado

Al terminar:

1. Informa el archivo creado o modificado.
2. Resume las reglas de validación implementadas.
3. Indica cualquier validación que requiera confirmación del usuario.
