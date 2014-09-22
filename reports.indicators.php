<?php
require_once 'functions.php';
require_once 'functions.omat.php';
$section = 6;
$load_menu = 3;
$sub_page = 2;

$id = (int)$_GET['id'];
$list = $db->query("SELECT i.*, t.name AS type_name,
  (SELECT COUNT(*) FROM mfa_indicators_formula
    JOIN mfa_groups ON mfa_indicators_formula.mfa_group = mfa_groups.id
  WHERE mfa_groups.dataset = $project AND indicator = i.id) AS formula
FROM mfa_indicators i
  JOIN mfa_indicators_types t ON i.type = t.id
WHERE i.dataset = $project OR i.dataset IS NULL
ORDER BY i.id");

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php echo $header ?>
    <title>Indicators | <?php echo SITENAME ?></title>
    <style type="text/css">
    h2{font-size:23px}
    .badge{z-index:100}
    </style>
  </head>

  <body>

<?php require_once 'include.header.php'; ?>

  <h1>Indicators</h1>

  <ol class="breadcrumb">
    <li><a href="omat/<?php echo $project ?>/dashboard">Dashboard</a></li>
    <li class="active">Indicators</li>
  </ol>

  <div class="row">

  <?php $type = false; foreach ($list as $row) { ?>

    <?php if ($row['type_name'] != $type) { ?>
    <?php if ($type) { ?></div></div><?php } ?>
    <div class="col-md-4">
      <h2><?php echo $row['type_name'] ?></h2>
    <div class="list-group">
    <?php } $type = $row['type_name']; ?>

      <a href="omat/<?php echo $project ?>/reports-indicator/<?php echo $row['id'] ?>" class="list-group-item">
        <h4 class="list-group-item-heading"><?php echo $row['name'] ?></h4>
        <p class="list-group-item-text"><?php echo truncate($row['description'],140) ?>
        <?php if ($row['formula']) { ?>
          <span class="badge pull-right"><i class="fa fa-bar-chart"></i></span>
        <?php } ?>
        </p>
      </a>

  <?php } ?>

  </div>

  </div>

  </div>

  <p class="clear"><span class="badge"><i class="fa fa-bar-chart"></i></span> These indicators are automatically calculated for your dataset.</p>


<?php require_once 'include.footer.php'; ?>

  </body>
</html>
