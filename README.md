# ubrr_vm

#############################################
#                Инструкция                 #
#############################################


1) Перейти в админ-панели сайта в Расширения->Менеджер расширений, в форме загрузки файла выбрать этот архив, нажать кнопку "Загрузить и установить"

2) Скопировать сгенерированные файлы личного сертификата и приватного ключа в папку корень_сайта/plugins/vmpayment/ubrir/certs c именами user.pem и user.key соответственно

3) Активировать платежную систему в админ-панели (VirtueMart->Payment Methods)

4) Настроить модуль используя персональные данные на странице настроек

5) Прописать сформированный на странице настроек URL в настройках ПЦ Uniteller корень_сайта/plugins/vmpayment/ubrir/uniteller.php




#############################################
#          Инструкция по обновлению сетификта      #
#############################################


Скопировать новый сертификат в папку корень_сайта/plugins/vmpayment/ubrir/certs c именем ubrir.crt



#############################################
#              Инструкция по удалению      #
#############################################


Удалить модуль в админ.панели (Расширения->Менеджер расширений)
