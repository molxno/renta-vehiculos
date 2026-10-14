## Qué cambia
<!-- 1 a 3 líneas -->
## Ticket
SCRUM-
## Cómo probarlo
<!-- Pasos o comando; captura si hay UI -->
## Antes de pedir revisión
- [ ] `composer ci:check` pasa en local
- [ ] Todo lo nuevo tiene tests y la cobertura sigue en 100 %
- [ ] Sigue docs/estandar-codigo.md (capas, tipos, sin lógica en controladores)
- [ ] La pantalla coincide con docs/design/ o Figma (si aplica)
- [ ] Sin secretos ni .env
- [ ] El PR va hacia dev
## Para quien revisa
- [ ] Cumple el ticket
- [ ] Las pruebas comprueban comportamiento, no solo recorren líneas
- [ ] CI en verde
