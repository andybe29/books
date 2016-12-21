# simple library #

организация хранилища элкниг на Google Drive через соответствующий API

config.php: конфигурация
 * $config->OPDS - адрес OPDS
 * $config->Path->OPDS - путь к каталогу OPDS (доступен для записи)
 * $config->Path->Upload - путь к каталогу загрузки *.fb2  (доступен для записи)
 * [credentials] (https://console.developers.google.com/apis/credentials):
 * $config->Client->ID - Client ID
 * $config->Client->Secret - Client Secret
 * $config->Client->URL - Authorised redirect URI
 * [project] (https://console.developers.google.com/iam-admin/settings/project):
 * $config->Project->ID - Project ID
 * $config->Project->Number - Project number
 * $config->Project->Email - Compute Engine default service account (см. в пункте IAM)
 * $config->Project->Folder - id папки на Google Drive, куда будут загружаться архивы
 * $config->db - доступ к MySQL

logs: каталог для логов, разрешить запись

upload.php: загрузка *.fb2 файлов на сервер в $config->Path->Upload

ajax.php: бэкенд
 * books - выбока списка букв, авторов, книг из БД
 * dbase.gdrive - синхронизация хранилища; удаление из БД записей о файлах, отсутствующих на Google Drive
 * gdrive.dbase - синхронизация хранилища; добавление в БД отсутствующих записей о файлах на Google Drive
 * gdrive.delete - удаление файла с Google Drive и записи из БД
 * gdrive.upload - копирование файлов с сервера на Google Drive
   * исходный файл архивируется в zip
   * копируется (если файл существует, то перезаписывается)
   * добавляется/обновляется запись в БД
   * удаляются fb2 и zip файлы
 * opds - генерация OPDS