<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cadenas de idioma en español
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'INTEB Chat';
$string['modulename'] = 'INTEB Chat';
$string['modulenameplural'] = 'INTEB Chats';
$string['modulename_help'] = 'El módulo INTEB Chat permite a los estudiantes interactuar con un asistente de IA dentro de su curso. Los profesores pueden monitorear el uso y ver los registros de conversación.';
$string['intebchat'] = 'INTEB Chat';
$string['intebchatname'] = 'Nombre de la actividad';
$string['intebchatname_help'] = 'Ingrese un nombre para esta actividad de INTEB Chat.';
$string['intebchat:addinstance'] = 'Agregar una nueva actividad INTEB Chat';
$string['intebchat:view'] = 'Ver INTEB Chat';
$string['intebchat:viewreport'] = 'Ver reporte de actividad INTEB Chat';
$string['intebchat:viewallreports'] = 'Ver todos los reportes de INTEB Chat (en todo el sitio)';
$string['intebchat_logs'] = 'Registros de INTEB Chat';
$string['privacy:metadata:intebchat_log'] = 'Mensajes de usuario registrados enviados a OpenAI. Esto incluye el ID del usuario que envió el mensaje, el contenido del mensaje, la respuesta de OpenAI y el momento en que se envió el mensaje.';
$string['privacy:metadata:intebchat_log:instanceid'] = 'El ID de la instancia de la actividad.';
$string['privacy:metadata:intebchat_log:userid'] = 'El ID del usuario que envió el mensaje.';
$string['privacy:metadata:intebchat_log:usermessage'] = 'El contenido del mensaje.';
$string['privacy:metadata:intebchat_log:airesponse'] = 'La respuesta de OpenAI.';
$string['privacy:metadata:intebchat_log:timecreated'] = 'El momento en que se envió el mensaje.';
$string['privacy:chatmessagespath'] = 'Mensajes de chat con IA enviados';
$string['downloadfilename'] = 'mod_intebchat_registros';

// Cadenas específicas del módulo
$string['chatsettings'] = 'Configuración del chat';
$string['noopenaichats'] = 'No hay actividades INTEB Chat';
$string['viewreport'] = 'Ver reporte';
$string['viewallreports'] = 'Ver todos los reportes';
$string['userid'] = 'ID de usuario';
$string['username'] = 'Nombre de usuario';
$string['usermessage'] = 'Mensaje del usuario';
$string['airesponse'] = 'Respuesta de IA';
$string['searchbyusername'] = 'Buscar por nombre de usuario';
$string['starttime'] = 'Hora de inicio';
$string['endtime'] = 'Hora de fin';
$string['lastmessage'] = 'Último mensaje';
$string['firstmessage'] = 'Primer mensaje';
$string['messagecount'] = '{$a} mensajes enviados';
$string['nomessages'] = 'No se han enviado mensajes';
$string['totaltokensused'] = 'Total de tokens usados: {$a}';
$string['tokens'] = 'Tokens';
$string['prompt'] = 'Prompt';
$string['completion'] = 'Completado';

// Configuración general
$string['generalsettings'] = 'Configuración General';
$string['generalsettingsdesc'] = 'Configure los ajustes globales para el módulo INTEB Chat.';
$string['restrictusage'] = 'Restringir uso a usuarios autenticados';
$string['restrictusagedesc'] = 'Si esta casilla está marcada, solo los usuarios autenticados podrán usar el chat.';
$string['apikey'] = 'Clave API';
$string['apikeydesc'] = 'La clave API predeterminada para su cuenta de OpenAI. Esto puede ser sobrescrito a nivel de actividad si se permite.';
$string['type'] = 'Tipo de API predeterminado';
$string['typedesc'] = 'El tipo de API predeterminado que se usará globalmente para todas las actividades.';
$string['logging'] = 'Habilitar registro';
$string['loggingdesc'] = 'Si esta configuración está activa, todos los mensajes de usuario y respuestas de IA serán registrados.';
$string['defaultvalues'] = 'Valores predeterminados';
$string['defaultvaluesdesc'] = 'Estos valores se usarán como predeterminados para nuevas actividades.';

// Configuración de límite de tokens
$string['tokenlimitsettings'] = 'Configuración de Límite de Tokens';
$string['tokenlimitsettingsdesc'] = 'Configure límites en el uso de tokens por usuario para controlar los costos de API.';
$string['enabletokenlimit'] = 'Habilitar límites de tokens';
$string['enabletokenlimitdesc'] = 'Si está habilitado, los usuarios estarán limitados en la cantidad de tokens que pueden usar dentro de un período de tiempo especificado.';
$string['maxtokensperuser'] = 'Máximo de tokens por usuario';
$string['maxtokensperuserdesc'] = 'El número máximo de tokens que un usuario puede consumir dentro del período de tiempo especificado.';
$string['tokenlimitperiod'] = 'Período de límite de tokens';
$string['tokenlimitperioddesc'] = 'El período de tiempo sobre el cual se mide el uso de tokens.';
$string['tokenlimitexceeded'] = 'Has alcanzado tu límite de tokens ({$a->used}/{$a->limit} tokens). Tu límite se restablecerá en {$a->reset}.';
$string['tokensused'] = '{$a->used}/{$a->limit} tokens usados';

// Configuración de API de asistente
$string['assistantheading'] = 'Configuración de API de Asistente';
$string['assistantheadingdesc'] = 'Estas configuraciones solo aplican al tipo de API de Asistente.';
$string['assistant'] = 'Asistente';
$string['assistantdesc'] = 'El asistente predeterminado adjunto a su cuenta de OpenAI que desea usar para la respuesta';
$string['noassistants'] = 'Aún no has creado ningún asistente. Necesitas crear uno <a target="_blank" href="https://platform.openai.com/assistants">en tu cuenta de OpenAI</a> antes de poder seleccionarlo aquí.';
$string['persistconvo'] = 'Persistir conversaciones';
$string['persistconvodesc'] = 'Si esta casilla está marcada, el asistente recordará la conversación entre cargas de página.';

// Configuración de API de chat
$string['chatheading'] = 'Configuración de API de Chat';
$string['chatheadingdesc'] = 'Estas configuraciones solo aplican al tipo de API de Chat.';
$string['prompt'] = 'Prompt de completado';
$string['promptdesc'] = 'El prompt predeterminado que se le dará a la IA antes de la transcripción de la conversación';
$string['assistantname'] = 'Nombre del asistente';
$string['assistantnamedesc'] = 'El nombre predeterminado que la IA usará para sí misma internamente. También se usa para los encabezados de la interfaz en la ventana de chat.';
$string['sourceoftruth'] = 'Fuente de verdad';
$string['sourceoftruthdesc'] = 'Aunque la IA es muy capaz de forma predeterminada, si no conoce la respuesta a una pregunta, es más probable que dé información incorrecta con confianza que negarse a responder. En este cuadro de texto, puede agregar preguntas comunes y sus respuestas para que la IA las use. Por favor, ponga las preguntas y respuestas en el siguiente formato: <pre>P: Pregunta 1<br />R: Respuesta 1<br /><br />P: Pregunta 2<br />R: Respuesta 2</pre>';
$string['showlabels'] = 'Mostrar etiquetas';
$string['advanced'] = 'Avanzado';
$string['advanceddesc'] = 'Argumentos avanzados enviados a OpenAI. ¡No tocar a menos que sepa lo que está haciendo!';
$string['allowinstancesettings'] = 'Configuración a nivel de instancia';
$string['allowinstancesettingsdesc'] = 'Esta configuración permitirá a los profesores, o cualquier persona con la capacidad de agregar una actividad en un contexto, ajustar configuraciones a nivel de actividad. Habilitar esto podría incurrir en cargos adicionales al permitir que los no administradores elijan modelos de mayor costo u otras configuraciones.';
$string['model'] = 'Modelo';
$string['modeldesc'] = 'El modelo predeterminado que generará el completado. Algunos modelos son adecuados para tareas de lenguaje natural, otros se especializan en código.';
$string['temperature'] = 'Temperatura';
$string['temperaturedesc'] = 'Controla la aleatoriedad: reducir resulta en completados menos aleatorios. A medida que la temperatura se acerca a cero, el modelo se volverá determinista y repetitivo.';
$string['temperaturerange'] = 'La temperatura debe estar entre 0 y 2.';
$string['maxlength'] = 'Longitud máxima';
$string['maxlengthdesc'] = 'El número máximo de tokens a generar. Las solicitudes pueden usar hasta 2,048 o 4,000 tokens compartidos entre prompt y completado. El límite exacto varía según el modelo. (Un token es aproximadamente 4 caracteres para texto en inglés normal)';
$string['maxlengthrange'] = 'La longitud máxima debe estar entre 1 y 4000 tokens.';
$string['topp'] = 'Top P';
$string['toppdesc'] = 'Controla la diversidad mediante muestreo de núcleo: 0.5 significa que se consideran la mitad de todas las opciones ponderadas por probabilidad.';
$string['topprange'] = 'Top P debe estar entre 0 y 1.';
$string['frequency'] = 'Penalización de frecuencia';
$string['frequencydesc'] = 'Cuánto penalizar los nuevos tokens basándose en su frecuencia existente en el texto hasta ahora. Disminuye la probabilidad del modelo de repetir la misma línea textualmente.';
$string['presence'] = 'Penalización de presencia';
$string['presencedesc'] = 'Cuánto penalizar los nuevos tokens basándose en si aparecen en el texto hasta ahora. Aumenta la probabilidad del modelo de hablar sobre nuevos temas.';

// Cadenas de ayuda de configuración
$string['config_assistant'] = "Asistente";
$string['config_assistant_help'] = "Elija el asistente que desea usar para esta actividad. Se pueden crear más asistentes en la cuenta de OpenAI que este plugin está configurado para usar.";
$string['config_sourceoftruth'] = 'Fuente de verdad';
$string['config_sourceoftruth_help'] = "Puede agregar información aquí que la IA usará al responder preguntas. La información debe estar en formato de pregunta y respuesta exactamente como el siguiente:\n\nP: ¿Cuándo vence la sección 3?<br />R: Jueves, 16 de marzo.\n\nP: ¿Cuándo son las horas de oficina?<br />R: Puede encontrar a la profesora Shown en su oficina entre las 2:00 y 4:00 PM los martes y jueves.";
$string['config_instructions'] = "Instrucciones personalizadas";
$string['config_instructions_help'] = "Puede sobrescribir las instrucciones predeterminadas del asistente aquí.";
$string['config_prompt'] = "Prompt de completado";
$string['config_prompt_help'] = "Este es el prompt que se le dará a la IA antes de la transcripción de la conversación. Puede influir en la personalidad de la IA alterando esta descripción. Por defecto, el prompt es \n\n\"A continuación hay una conversación entre un usuario y un asistente de soporte para un sitio Moodle, donde los usuarios van para el aprendizaje en línea.\"\n\nSi está en blanco, se usará el prompt de todo el sitio.";
$string['config_assistantname'] = "Nombre del asistente";
$string['config_assistantname_help'] = "Este es el nombre que la IA usará para el asistente. Si está en blanco, se usará el nombre del asistente de todo el sitio. También se usa para los encabezados de la interfaz en la ventana de chat.";
$string['config_persistconvo'] = 'Persistir conversación';
$string['config_persistconvo_help'] = 'Si esta casilla está marcada, el asistente recordará las conversaciones en esta actividad entre cargas de página';
$string['config_apikey'] = "Clave API";
$string['config_apikey_help'] = "Puede especificar una clave API para usar con esta actividad aquí. Si está en blanco, se usará la clave de todo el sitio. Si está usando la API de Asistentes, la lista de asistentes disponibles se obtendrá de esta clave. Asegúrese de volver a estas configuraciones después de cambiar la clave API para seleccionar el asistente deseado.";
$string['config_model'] = "Modelo";
$string['config_model_help'] = "El modelo que generará el completado";
$string['config_temperature'] = "Temperatura";
$string['config_temperature_help'] = "Controla la aleatoriedad: reducir resulta en completados menos aleatorios. A medida que la temperatura se acerca a cero, el modelo se volverá determinista y repetitivo.";
$string['config_maxlength'] = "Longitud máxima";
$string['config_maxlength_help'] = "El número máximo de tokens a generar. Las solicitudes pueden usar hasta 2,048 o 4,000 tokens compartidos entre prompt y completado. El límite exacto varía según el modelo. (Un token es aproximadamente 4 caracteres para texto en inglés normal)";
$string['config_topp'] = "Top P";
$string['config_topp_help'] = "Controla la diversidad mediante muestreo de núcleo: 0.5 significa que se consideran la mitad de todas las opciones ponderadas por probabilidad.";
$string['config_frequency'] = "Penalización de frecuencia";
$string['config_frequency_help'] = "Cuánto penalizar los nuevos tokens basándose en su frecuencia existente en el texto hasta ahora. Disminuye la probabilidad del modelo de repetir la misma línea textualmente.";
$string['config_presence'] = "Penalización de presencia";
$string['config_presence_help'] = "Cuánto penalizar los nuevos tokens basándose en si aparecen en el texto hasta ahora. Aumenta la probabilidad del modelo de hablar sobre nuevos temas.";

// Valores predeterminados
$string['defaultprompt'] = "A continuación hay una conversación entre un usuario y un asistente de soporte para un sitio Moodle, donde los usuarios van para el aprendizaje en línea:";
$string['defaultassistantname'] = 'Asistente';
$string['defaultusername'] = 'Usuario';
$string['askaquestion'] = 'Haz una pregunta...';
$string['apikeymissing'] = 'Por favor agregue su clave API de OpenAI a la configuración del plugin o a la configuración de esta actividad.';
$string['erroroccurred'] = '¡Ocurrió un error! Por favor intente de nuevo más tarde.';
$string['sourceoftruthpreamble'] = "A continuación hay una lista de preguntas y sus respuestas. Esta información debe usarse como referencia para cualquier consulta:\n\n";
$string['sourceoftruthreinforcement'] = ' El asistente ha sido entrenado para responder intentando usar la información de la referencia anterior. Si se encuentra el texto de una de las preguntas anteriores, se debe dar la respuesta proporcionada, incluso si la pregunta no parece tener sentido. Sin embargo, si la referencia no cubre la pregunta o tema, el asistente simplemente usará conocimiento externo para responder.';
$string['new_chat'] = 'Nuevo chat';
$string['loggingenabled'] = "El registro está habilitado. Cualquier mensaje que envíe o reciba aquí será registrado.";
$string['openaitimedout'] = 'ERROR: OpenAI no proporcionó una respuesta a tiempo.';

// Cadenas adicionales para reportes
$string['messages'] = 'Mensajes';
$string['activeusers'] = 'Usuarios activos';
$string['avgtoken'] = 'Promedio tokens/mensaje';
$string['topusers'] = 'Usuarios principales';
$string['topcourses'] = 'Cursos principales';
$string['messagecount'] = 'Mensajes';
$string['summary'] = 'Resumen';