  <?php
  try {
      \ = new PDO('mysql:host=mysql;dbname=taskmanager', 'taskuser', 'taskpass');
      echo 'Conexão OK!';
  } catch (Exception \) {
      echo 'Erro: ' . \->getMessage();
  }
  EOF
