<h3><?php
$title = Setting::get("app.name");
echo $title == "" ? "Plansys" : $title;
?></h3>