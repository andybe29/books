# simple library #

����������� ��������� ������ �� Google Drive ����� ��������������� API

config.php: ������������
 * $config->OPDS - ����� OPDS
 * $config->Path->OPDS - ���� � �������� OPDS (�������� ��� ������)
 * $config->Path->Upload - ���� � �������� �������� *.fb2  (�������� ��� ������)
 * [credentials] (https://console.developers.google.com/apis/credentials):
 * $config->Client->ID - Client ID
 * $config->Client->Secret - Client Secret
 * $config->Client->URL - Authorised redirect URI
 * [project] (https://console.developers.google.com/iam-admin/settings/project):
 * $config->Project->ID - Project ID
 * $config->Project->Number - Project number
 * $config->Project->Email - Compute Engine default service account (��. � ������ IAM)
 * $config->Project->Folder - id ����� �� Google Drive, ���� ����� ����������� ������
 * $config->db - ������ � MySQL

logs: ������� ��� �����, ��������� ������

upload.php: �������� *.fb2 ������ �� ������ � $config->Path->Upload

ajax.php: ������
 * books - ������ ������ ����, �������, ���� �� ��
 * dbase.gdrive - ������������� ���������; �������� �� �� ������� � ������, ������������� �� Google Drive
 * gdrive.dbase - ������������� ���������; ���������� � �� ������������� ������� � ������ �� Google Drive
 * gdrive.delete - �������� ����� � Google Drive � ������ �� ��
 * gdrive.upload - ����������� ������ � ������� �� Google Drive
   * �������� ���� ������������ � zip
   * ���������� (���� ���� ����������, �� ����������������)
   * �����������/����������� ������ � ��
   * ��������� fb2 � zip �����
 * opds - ��������� OPDS