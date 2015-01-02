Discuzcode Video
================
A simplified way to support video sharing under BBCode environments
I did this long time ago. I've just dig it up and dust it out.

Some video URL parsing function may still be useful.

Installation
============

 1. login to your Discuz! installation FTP/SFTP

 2. goes to **your Discuz! dir**`/include`

 3. upload this folder to the **your Discuz! dir**`/include` as
    **your Discuz! dir**`/include/discuzcode-video`

 4. open `discuzcode.func.php`

 5. find a function named 'discuzcode'

 6. inside the function, find a line that start with: 'if(!$bbcodeoff && $allowbbcode) {'

 7. before the line you found in step 5, add this line

    ```php
       require(__DIR__.'/discuzcode-video/common.inc.php');
    ```

 8. save and exit

 9. done. test it.

