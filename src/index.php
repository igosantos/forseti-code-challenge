<!DOCTYPE html>
<html>
<head>
<title>foseti-code-challenge</title>
</head>
<body>

<?php
/*

# Teste para desenvolvedor PHP FORSETI #
Este repositório é o resultado do teste descrito em https://git-forseti.github.io/forseti-code-challenge/, realizado por Igo Santos <web@igosantos.com>.

O objetivo é desenvolver um crawler para ler a página de notícias sobre as compras do governo federal (https://www.gov.br/compras/pt-br/acesso-a-informacao/noticias), e entregá-las em uma interface amigável.

# Requisitos #

 - Usar PHP;
 - Extrair as informações necessárias (manchete, data/hora e link dos detalhes);
 - Buscar 5 páginas;
 - Salvar os dados estruturados em um banco de dados ou num arquivo csv;
 - Garantir que as notícias não se dupliquem no registro final;

# Como testar o aplicativo #

O app pode ser testado no endereço http://igosantos.com/forseti .
O código-fonte está disponível em http://igosantos.com/forseti/forseti.tar.gz

*/

if($_SERVER['USERNAME'] == 'igo') {
    $db_hostname = "localhost";
    $db_username = "forseti";
    $db_password = "forseti";
    $db_name = "forseti";
} else {
    $db_hostname = "mysql.igosantos.com";
    $db_username = "forseti_igo";
    $db_password = "forseti123";
    $db_name = "forseti";    
}

//obtém os artigos à partir do url, e os insere no banco de dados.
function get_articles($dbconnect,$url) {

    $pageContent = file_get_contents($url);

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($pageContent);
    $articles = $doc->getElementsByTagName('article');
    $finder = new DomXPath($doc);
    $classname = 'article';


    if ($dbconnect->connect_error) {
      die("Database connection failed: " . $dbconnect->connect_error);
    }
    
    
    for ($i=0;$i<$articles->length;$i++) {
        $article = $articles->item($i);
        $h2 = $article->getElementsByTagName('h2');
        $children = $article->getElementsByTagName('*');
        $title = trim($h2->item(0)->nodeValue);
        $row_title = $title;
    
        for ($ii=0;$ii<$children->length;$ii++) {
            $child = $children->item($ii);
            $childClass =  $child->getAttribute('class');
            $childID =  $child->getAttribute('id');
            $childText = trim($child->nodeValue);
            $hasChildNodes = 0;
            $grandChildName = "";
            $grandChildValue = "";
            
            
            if($child->nodeName == "a" && $child->getAttribute('class') == "summary url") {
                $row_href = $child->getAttribute('href');
            }
    
            if($child->hasChildNodes() && $childClass == "summary-view-icon") {
                $hasChildNodes =$child->childNodes->length; 
                for($iii=0;$iii<$child->childNodes->length;$iii++) {
                    $grandChild = $child->childNodes->item($iii);
                    if($grandChild->nodeName == "i" && $grandChild->hasAttribute('class')) {
                        $grandChildClass = trim($grandChild->getAttribute('class'));
                        $grandChildID = $grandChildClass;
                    }
                }
    
                if($grandChildClass=="icon-day") {
                    $row_day = $childText;
                    $row_day = substr($row_day,6,4) . "-" . substr($row_day,3,2) . "-" . substr($row_day,0,2);
                }
    
                if($grandChildClass=="icon-hour") $row_hour = $childText;
                    
            }
    
    
        }
        
        $sql = "INSERT INTO news (datahora,titulo,href) VALUES ('".$row_day."','".$row_title."','".$row_href."')";
        $query = mysqli_query($dbconnect, $sql);
    }
        
}

//conectando e criando o banco de dados.
$dbconnect=mysqli_connect($db_hostname,$db_username,$db_password,$db_name) or die(mysqli_error($dbconnect));
$sql_create = "CREATE TABLE IF NOT EXISTS news (datahora DATETIME,titulo TEXT, href varchar(1024) CHARACTER SET utf8 COLLATE utf8_general_ci UNIQUE DEFAULT NULL);";

$query = mysqli_query($dbconnect, $sql_create);
$query = mysqli_query($dbconnect, "SELECT * FROM news ORDER BY datahora DESC") or die (mysqli_error($dbconnect));

//obtendo artigos
if(isset($_GET['update']) && $_GET['update'] == "1") {
    get_articles($dbconnect,'https://www.gov.br/compras/pt-br/acesso-a-informacao/noticias');
    get_articles($dbconnect,'https://www.gov.br/compras/pt-br/acesso-a-informacao/noticias?b_start:int=30');
    get_articles($dbconnect,'https://www.gov.br/compras/pt-br/acesso-a-informacao/noticias?b_start:int=60');
    get_articles($dbconnect,'https://www.gov.br/compras/pt-br/acesso-a-informacao/noticias?b_start:int=90');
}

//mostrando resultados
echo "<ul>\n";
while ($row = mysqli_fetch_array($query)) {
    $data = date('d/m/Y',strtotime($row['datahora']));
    echo "
        <li>
            <a href=\"{$row['href']}\">
            {$row['titulo']}
            <span>${data}</span>
            </a>
        </li>
";
}
echo "</ul>\n";

//fechando conexão com banco de dados
mysqli_close($dbconnect);

?>

</body>
</html>
