# Probar Diástoles en local

## Requisito

Instala y abre Docker Desktop.

## Primera ejecución

Desde la raíz del repositorio:

```bash
./tools/setup-local.sh
```

El script crea WordPress, activa el plugin y publica la página de la
experiencia.

- Experiencia: <http://localhost:8080/>
- Administración: <http://localhost:8080/wp-admin/>
- Usuario: `admin`
- Contraseña: `diastoles-local`

Estas credenciales son exclusivamente para el entorno local.

## Primera prueba sin consumir API

El plugin comienza en modo `Mock`:

1. Abre la experiencia en una ventana normal.
2. Acepta el consentimiento y responde usando las palabras `coffee`, `rain`,
   `wood`, `home`, `safety`, `nostalgia`, `family` o `future`. El clasificador
   local reconoce estas etiquetas.
3. Abre una ventana privada y crea un segundo participante.
4. Responde utilizando alguna de las mismas palabras.
5. Entra en `Diastoles → Connections` en el panel y aprueba la conexión.
6. Vuelve a ambas ventanas y abre `Resonances`.

El modo Mock no envía ningún texto fuera del ordenador.

## Probar Anthropic

1. Entra en `Diastoles → AI settings`.
2. Pega una API key de Anthropic.
3. Conserva el modelo `claude-haiku-4-5-20251001`.
4. Cambia `Mode` a `Live — Anthropic API`.
5. Guarda los ajustes.

Las respuestas creadas a partir de ese momento se procesarán mediante la
Messages API de Anthropic.

## Reescribir las preguntas

Entra en `Diastoles → Questions` dentro del panel de WordPress. Desde esa
pantalla puedes:

- Editar la pregunta y su repregunta opcional.
- Cambiar tema e intensidad.
- Activar o desactivar una pregunta sin borrar respuestas anteriores.
- Añadir preguntas nuevas.

Todas las preguntas visibles para participantes deben estar en inglés, aunque
cada participante puede responder en cualquier idioma.

## Probar perfiles y exportación

1. Abre la página pública en una ventana privada para crear una sesión nueva.
2. Despliega `A few optional coordinates` y responde solo algunos campos.
3. Comprueba que también puedes dejar todos los campos en blanco.
4. Tras entrar, abre `Your traces → Your optional coordinates` para editar o
   retirar esos datos.
5. En administración, revisa `Diastoles → Participants`.
6. En `Diastoles → Data & Export`, descarga el ZIP y comprueba que contiene
   CSV, JSON y un `manifest.json`, pero no hashes de sesión o recuperación.

## Probar límites y protección antibot

- Envía cinco respuestas desde la misma sesión: la sexta debe quedar bloqueada
  hasta el siguiente día local.
- Intenta enviar una respuesta durante los primeros diez segundos tras recibir
  la pregunta: debe pedirte esperar unos segundos.
- Repite exactamente un fragmento anterior: debe pedir contenido nuevo.
- El campo `website` es un honeypot invisible; si se rellena mediante una
  petición automatizada, el servidor rechaza el envío.

## Trabajo diario

Los archivos del directorio `diastoles/` están montados dentro de WordPress.
Los cambios en PHP, JavaScript o CSS aparecen al recargar la página.

```bash
# Iniciar
docker compose up -d

# Ver logs
docker compose logs -f wordpress

# Detener sin borrar los datos
docker compose down
```

Para empezar de cero y eliminar únicamente los volúmenes locales de este
proyecto:

```bash
docker compose down --volumes
./tools/setup-local.sh
```
