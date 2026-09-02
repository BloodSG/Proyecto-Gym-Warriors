# Proyecto-Gym-Warriors
Proyecto que integra a las asignaturas de Programación Web, Fundamentos de Ingeniería de Software y Taller de Bases de Datos 
Con el objetivo de crear un sistema web dedicado al gimnasio Gym Warriors 

Instrucciones para el correcto uso de git-github y llevar un correcto uso de versiones:
-Empezar por crear un repositorio en tu computadora, primero ubica una carpeta en un tu laptop y crea un repositorio con:
git init
-Después hacer un git clone del repositorio, a continuación, el comando:
git clone [URL]: Descarga un proyecto completo que ya existe en la nube (como GitHub) a tu computadora.
Listo, con eso ya tendrás el repositorio en tu computadora y podrás empezar a modificar el código de manera local, una vez que hayas echo eso, para guardar un cambio en el repositorio.


Cabe destacar que solo harás este procemiento cada que hagas un avance significativo, esto con el fin de evitar llenar el repositorio con commits innecesarios  
Favor de seguir estos pasos cuidadosamente
1-	git pull: Descarga y fusiona los cambios más recientes del servidor a tu computadora (lo opuesto a push).
Vas a crear una rama antes de empezar a programar para no arruinar el main
2-	git checkout -b [nombre-rama]: Comando rápido: crea una rama y se cambia a ella de una vez.
Ya con eso podrás empezar a programar, una vez agregado tus cambios al código harás lo siguiente:
3-	git status: Te dice qué archivos has modificado, cuáles vas a guardar y qué falta por agregar. Es el comando de "orientación".
4-	git add . : Añade todos los archivos modificados de un golpe.
5-	git commit -m "mensaje": Guarda permanentemente los cambios en el historial con un mensaje el cual debe de ser lo más descriptivo posible, si es necesario un párrafo completo.
Ejecutas el código revisando de no haber corrompido el código de los demás y que tu código funcione adecuadamente.
Entonces habrá dos caminos, si todo funciona correctamente deberás de hacer lo siguiente: 
6-	git checkout main: para mudarte al main 
7-	git merge [nombre-rama]: Une los cambios de la rama indicada a la rama donde estás actualmente (usualmente para llevar cambios a main).
8-	git push origin [rama]: Sube tus commits locales al repositorio en la nube.
Si no funciona, lo que deberás hacer es que corregirás el código hasta que funcione y empezar desde el paso 4.
Otra opción es hacer git reset –soft HEAD~1  para revertir el ultimo commit pero conservar tus archivos.
