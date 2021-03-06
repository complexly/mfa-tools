<?php
require_once 'functions.php';
require_once 'functions.omat.php';
$section = 6;
$load_menu = 1;
$sub_page = 2;

$id = (int)$_GET['id'];
$project = (int)$_GET['project'];

$info = $db->record("SELECT s.*, t.name AS type, o.status AS status_name
FROM mfa_sources s
  LEFT JOIN mfa_sources_types t ON s.type = t.id
  JOIN mfa_status_options o ON s.status = o.id
WHERE s.id = $id AND s.dataset = $project");

if (!$info->id) {
  die("This source was not found");
}

if ($_POST['fileform']) {
  if ($_FILES) {
    $original_name = $_FILES['file']['name'];
    $type = $_FILES['file']['type'];
    $size = $_FILES['file']['size']/1024;
  } else {
    $original_name = false;
    $type = false;
  }
  $post = array(
    'name' => html($_POST['name']),
    'original_name' => mysql_clean($original_name),
    'url' => mysql_clean($_POST['url']),
    'dataset' => $project,
    'source' => $id,
    'type' => mysql_clean($type),
    'size' => (int)$size,
  );
  $db->insert("mfa_files",$post);
  $file_id = $db->lastInsertId();
  if ($_FILES['file']['name']) {
    if (!$_FILES['file']['error']) {
      $location = UPLOAD_PATH . "$project.$id.$file_id";
      if (!is_writable(UPLOAD_PATH)) {
        $error = "Directory is not writeable.";
        echo $error;
        die();
      }
      move_uploaded_file($_FILES['file']['tmp_name'], $location);
    } else {
      $error = "File could not be uploaded.";
      echo $error;
      die();
    }
  }
  header("Location: " . URL . "omat/$project/viewsource/$id/file-saved");
  exit();
}

if ($_GET['deletefile']) {
  $delete = (int)$_GET['deletefile'];
  $db->query("DELETE FROM mfa_files WHERE id = $delete AND dataset = $project");
  $file = UPLOAD_PATH . "$project.$id.$delete";
  if (file_exists($file)) {
    unlink($file);
  }
  header("Location: " . URL . "omat/$project/viewsource/$id/file-deleted");
  exit();
}

if ($_GET['status']) {
  $status = (int)$_GET['status'];
  $post = array(
    'status' => $status,
  );
  $db->update("mfa_sources",$post,"id = $id");
  header("Location: " . URL . "omat/$project/viewsource/$id");
  exit();
}

if ($_GET['deleteconfirm']) {
  $_GET['delete'] = true;
  $_GET['confirm'] = true;
}

if ($_GET['delete']) {
  $delete = (int)$_GET['id'];
  $data = $db->query("SELECT * FROM mfa_data WHERE source_id = $delete");
  if (count($data)) {
    $alert = "<li>You have data points associated with this data source. The data points will remain in place, but they won't have an associated source anymore.</li>";
  }
  
  if (!$alert || $_GET['confirm']) {
    $db->query("DELETE FROM mfa_sources WHERE id = $delete LIMIT 1");
    header("Location: " . URL . "omat/$project/sources/deleted");
    exit();
  }
}

if ($_GET['flag']) {
  $post = array(
    'flag' => (int)$_GET['flag'],
    'source' => $id,
  );
  $db->insert("mfa_sources_flags",$post);
  header("Location: " . URL . "omat/$project/viewsource/$id");
  exit();
} elseif ($_GET['unflag']) {
  $unflag = (int)$_GET['unflag'];
  $db->query("DELETE FROM mfa_sources_flags WHERE source = $id AND flag = $unflag");
  header("Location: " . URL . "omat/$project/viewsource/$id");
  exit();
}

$contact_leads = $db->query("SELECT 
  mfa_contacts.*
FROM mfa_leads
  JOIN mfa_contacts ON mfa_leads.to_contact = mfa_contacts.id
WHERE from_source = $id ORDER BY mfa_contacts.name");

$sources_leads = $db->query("SELECT 
  mfa_sources.*
FROM mfa_leads
  JOIN mfa_sources ON mfa_leads.to_source = mfa_sources.id
WHERE from_source = $id ORDER BY mfa_sources.name");

$referred_source = $db->query("SELECT 
  mfa_sources.*
FROM mfa_leads
  JOIN mfa_sources ON mfa_leads.from_source = mfa_sources.id
WHERE to_source = $id");

$referred_contact = $db->query("SELECT 
  mfa_contacts.*
FROM mfa_leads
  JOIN mfa_contacts ON mfa_leads.from_contact = mfa_contacts.id
WHERE to_source = $id");


$interactionlist = $db->query("SELECT * FROM mfa_activities WHERE dataset = $project ORDER BY name");

$interaction = $db->query("SELECT 
  l.*, a.name
FROM 
mfa_activities_log l
  JOIN mfa_activities a ON l.activity = a.id
WHERE l.source = $id ORDER BY start DESC");

$flags = $db->query("SELECT *,
  (SELECT COUNT(*) FROM mfa_sources_flags WHERE source = $id AND flag = mfa_special_flags.id) AS active
FROM mfa_special_flags WHERE dataset IS NULL OR dataset = $project ORDER BY name");

$files = $db->query("SELECT * FROM mfa_files WHERE source = $id ORDER BY name");

if ($_GET['file-deleted']) {
  $print = "File has been deleted";
} elseif ($_GET['file-saved']) { 
  $print = "File has been saved";
}

if ($_GET['activity-deleted']) {
  $print = "Activity has been deleted";
}

$status_options = $db->query("SELECT * FROM mfa_status_options ORDER BY id");

$associations = $db->query("SELECT mfa_groups.name AS groupname, mfa_materials.name AS material, l.id,
  (SELECT mfa_groups.name FROM mfa_groups WHERE mfa_materials.mfa_group = mfa_groups.id) AS material_groupname
FROM mfa_material_links l
  LEFT JOIN mfa_groups ON l.mfa_group = mfa_groups.id
  LEFT JOIN mfa_materials ON l.material = mfa_materials.id
WHERE source = $id");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php echo $header ?>
    <title><?php echo $info->name ?> | <?php echo SITENAME ?></title>
    <style type="text/css">
    dd { margin-bottom:10px; }
    .form-inline select.small{width:140px}
    div.right,a.right{float:right;margin-left:5px}
    h2{font-size:1.3em;}
    #help{display:none;}
    .leads{margin-top:30px}
    #sourceleads{margin-top:53px}
    #error{display:none}
    #delete{margin-top:30px;float:right}
    #activitylist .makesmall{font-size:11px;opacity:0.7}
    .badge{margin-left:8px}
    .ellipsis th.short{width:90px;max-width:90px}
    .ellipsis th.shorter{width:70px;max-width:90px}
    .ellipsis td,.ellipsis th{width:230px;max-width:230px}
    ol.breadcrumb{clear:both;margin-top:45px}
    </style>
    <script type="text/javascript">
    $(function(){
      $("#showhelp").click(function(e){
        e.preventDefault();
        $("#help").slideToggle('fast');
      });
      $("#start_timer").click(function(){
        if ($("#start_timer").is(":checked")) {
          $("#activity_time").attr("disabled", true);
        } else {
          $("#activity_time").attr("disabled", false);
        }
      });
      $("#addcontact").submit(function(e){
        $("#addcontact button").attr("disabled",true);
        if ($("#organization").is(":checked")) {
          organization = 1;
        } else {
          organization = 0;
        }
        $.post("ajax.contact.php",{
          source: <?php echo $id ?>,
          action: 'addcontact',
          name: $("#contact_name").val(),
          organization: organization,
          project: <?php echo $project ?>,
          dataType: "json"
        }, function(data) {
          if (data.response == "OK") {
            $("#contactleads").show().prepend(data.message);
            $("#addcontact button").attr("disabled",false).val("");
            $("#contact_name").val('');
          } else {
            $("#error").html("There was a problem saving the contact. Please refresh the page. Report this error if it persists. Error 243.");
            $("#error").show();
          }
        },'json')
        .error(function(){
          $("#error").html("There was a problem saving the contact. Please refresh the page. Report this error if it persists. Error 244.");
          $("#error").show();
        });
        e.preventDefault();
      });
      $("#addsource").submit(function(e){
        $("#addsource button").attr("disabled",true);
        $.post("ajax.contact.php",{
          source: <?php echo $id ?>,
          action: 'addsource',
          name: $("#source_name").val(),
          project: <?php echo $project ?>,
          dataType: "json"
        }, function(data) {
          if (data.response == "OK") {
            $("#sourceleads").show().prepend(data.message);
            $("#addsource button").attr("disabled",false).val("");
            $("#source_name").val('');
          } else {
            $("#error").html("There was a problem saving the source. Please refresh the page. Report this error if it persists. Error 245.");
            $("#error").show();
          }
        },'json')
        .error(function(){
          $("#error").html("There was a problem saving the source. Please refresh the page. Report this error if it persists. Error 246.");
          $("#error").show();
        });
        e.preventDefault();
      });
      $("#addactivity").submit(function(e){
        $("#addactivity button").attr("disabled",true);
        if ($("#start_timer").is(":checked")) {
          timer = 1;
        } else {
          timer = 0;
        }
        $.post("ajax.contact.php",{
          source: <?php echo $id ?>,
          action: 'addactivity',
          type: $("#activity_type").val(),
          project: <?php echo $project ?>,
          time: $("#activity_time").val(),
          timer: timer,
          dataType: "json"
        }, function(data) {
          if (data.response == "OK") {
            $("#activitylist").show().prepend(data.message);
            $("#addactivity button").attr("disabled",false).val("");
            $("#activity_time").val('');
          } else {
            $("#error").html("There was a problem saving the activity. Please refresh the page. Report this error if it persists. Error 247.");
            $("#error").show();
          }
        },'json')
        .error(function(){
          $("#error").html("There was a problem saving the activity. Please refresh the page. Report this error if it persists. Error 248.");
          $("#error").show();
        });
        e.preventDefault();
      });
    });
    </script>
  </head>

  <body class="omat">

<?php require_once 'include.header.php'; ?>

  <div class="dropdown right">
    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown">
      Status: <?php echo $info->status_name; ?>
      <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
    <?php foreach ($status_options as $row) { ?>
      <li role="presentation"<?php if ($info->status == $row['id']) { echo ' class="active"'; } ?>>
        <a role="menuitem" tabindex="-1" href="omat/<?php echo $project ?>/viewsource/<?php echo $id ?>/status/<?php echo $row['id'] ?>">
          <?php if ($row['id'] == $info->status) { ?>
            <i class="fa fa-check"></i>
          <?php } ?>
          <?php echo $row['status'] ?>
        </a>
      </li>
    <?php } ?>
    </ul>
  </div>
    
  <?php foreach ($flags as $row) { ?>
    <a href="omat/<?php echo $project ?>/viewsource/<?php echo $info->id ?>/<?php echo $row['active'] ? "unflag" : "flag" ?>/<?php echo $row['id'] ?>" class="btn right <?php echo $row['active'] ? 'btn-success' : 'btn-default' ?>">
    <?php if ($row['active']) { ?><i class="fa fa-check"></i> <?php } ?>
    <?php echo $row['name'] ?></a>
  <?php } ?>
  <a href="omat/<?php echo $project ?>/source/<?php echo $info->id ?>" class="btn btn-primary right">Edit</a>

  <ol class="breadcrumb">
    <li><a href="omat/<?php echo $project ?>/dashboard">Dashboard</a></li>
    <li><a href="omat/<?php echo $project ?>/sources">Sources</a></li>
    <li><?php echo $info->name ?></li>
  </ol>

  <?php if ($print) { echo "<div class=\"alert alert-success\">$print</div>"; } ?>

  <?php if ($alert) { echo "<div class=\"alert alert-danger\"><ul>$alert</ul></div>"; ?>
  <p>
    <a href="omat/<?php echo $project ?>/viewsource/<?php echo $info->id ?>/deleteconfirm" class="btn btn-danger">Yes, I am sure. Delete this source</a>
    <a href="omat/<?php echo $project ?>/viewsource/<?php echo $info->id ?>" class="btn btn-default">No, cancel this operation</a>
    
  </p>

  <?php } else { ?>

  <dl class="dl-horizontal">

    <dt>ID</dt>
    <dd><?php echo $id ?></dd>

    <dt>Name</dt>
    <dd>
      <?php if ($info->belongs_to) { hierarchyTree($info->belongs_to); ?>
        <?php krsort($ancestors); foreach ($ancestors as $key => $value) { ?>
          <a href="omat/<?php echo $project ?>/viewcontact/<?php echo $value[0] ?>"><?php echo $value[1] ?></a> &raquo;
        <?php } ?>
      <?php } ?>
      <?php echo $info->name ?>
    </dd>

    <?php if ($info->type) { ?>

      <dt>Type</dt>
      <dd><?php echo $info->type ?></dd>

    <?php } ?>

    <?php if ($info->url) { ?>

      <dt>URL</dt>
      <dd><a href="<?php echo $info->url ?>"><?php echo $info->url ?></a></dd>

    <?php } ?>

    <?php if ($check->resource_management) { ?>
      <dt>Associations</dt>
      <?php foreach ($associations as $row) { ?>
        <dd>
          <?php echo $row['material'] ? $row['material_groupname'] : $row['groupname'] ?>
          <?php if ($row['material']) { ?> &raquo; <?php echo $row['material'] ?><?php } ?>
        </dd>
      <?php } ?>
      <dd><a href="omat/<?php echo $project ?>/materiallink/source/<?php echo $id ?>">
      <i class="fa fa-external-link-square"></i> 
      Manage associations</a></dd>
    <?php } ?>

    <?php if ($info->details) { ?>
      <dt>Notes</dt>
      <dd><?php echo $info->details ?></dd>
    <?php } ?>

    <dt>Added</dt>
    <dd><?php echo format_date("M d, Y", $info->created) ?></dd>

    <?php if (count($referred_contact) || count($referred_source)) { ?>
      <dt>Referred to by</dt>
      <?php if (is_array($referred_source)) { foreach ($referred_source as $row) { ?>
      <dd>
        <a href="omat/<?php echo $project ?>/viewsource/<?php echo $row['id'] ?>"><?php echo $row['name'] ?></a>
      </dd>
      <?php } } ?>
      <?php if (is_array($referred_contact)) { foreach ($referred_contact as $row) { ?>
      <dd>
        <a href="omat/<?php echo $project ?>/viewcontact/<?php echo $row['id'] ?>"><?php echo $row['name'] ?></a>
      </dd>
      <?php } } ?>
    <?php } ?>

  </dl>

  <h1>
    Manage Leads and Activity Log
    <a href="#help" id="showhelp" class="right"><i class="fa fa-question-circle"></i></a>
  </h1>

  <div class="alert alert-info" id="help">
    Here you can add the other contacts and sources that <?php echo $info->name ?> has 
    referred you to. This not only helps you keep track of this information, but it enables
    OMAT to create detailed statistics of where information came from.<br />
    By logging interaction with <?php echo $info->name ?>, OMAT can provide more details on 
    how much time you invested in obtaining your data and provide insight into efficiency and
    effectiveness.
    <?php if (!$check->time_log) { ?><br />
    <strong>Note</strong>: before you can start logging time, you need to active this option
    in your Project Settings.
    <?php } ?>
  </div>

  <div class="alert alert-danger" id="error"></div>

  <div class="container-fluid">

    <div class="row">
    
      <div class="col-md-<?php echo $check->time_log ? 4 : 6 ?>">
        <h2>Leads: Contacts</h2>

        <form method="post" class="form-inline" id="addcontact">

          <div class="form-group">
            <label class="sr-only">Name</label>
            <input type="text" id="contact_name" class="form-control" placeholder="Name" required />
            <button type="submit" class="btn btn-success">Add</button>
          </div>
            <div class="checkbox">
              <label>
                <input type="checkbox" id="organization" value="1" /> This is an organization
              </label>
            </div>

        </form>

        <div class="leads list-group" id="contactleads">
          <?php foreach ($contact_leads as $row) { ?>
            <a 
              class="list-group-item<?php if ($row['status'] == 2) { echo ' list-group-item-success'; } elseif ($row['status'] == 5) { echo ' list-group-item-danger'; } ?>" 
              href="omat/<?php echo $project ?>/viewcontact/<?php echo $row['id'] ?>">
              <i class="fa fa-<?php echo $row['organization'] ? 'building' : 'user' ?>"></i>
              <?php echo $row['name'] ?>
            </a>
          <?php } ?>
        </div>

      </div>

      <div class="col-md-<?php echo $check->time_log ? 4 : 6 ?>">
        <h2>Leads: Sources</h2>

        <form method="post" class="form-inline" id="addsource">

          <div class="form-group">
            <label class="sr-only">Name</label>
            <input type="text" id="source_name" class="form-control" placeholder="Name" required />
            <button type="submit" class="btn btn-success">Add</button>
          </div>

        </form>

        <div class="leads list-group" id="sourceleads">
          <?php foreach ($sources_leads as $row) { ?>
            <a 
              class="list-group-item<?php if ($row['status'] == 2) { echo ' list-group-item-success'; } elseif ($row['status'] == 5) { echo ' list-group-item-danger'; } ?>" 
              href="omat/<?php echo $project ?>/viewsource/<?php echo $row['id'] ?>">
              <?php echo $row['name'] ?>
            </a>
          <?php } ?>
        </div>

      </div>

      <div class="col-md-4<?php echo !$check->time_log ? ' hide' : ''; ?>">

        <h2>Activity Log</h2>

        <?php if (!count($interactionlist)) { ?>

        <p>Before you can log activities, please <a href="omat/<?php echo $project ?>/maintenance-activities">define possible activities</a>.</p>

        <?php } else { ?>

        <form method="post" class="form-inline" id="addactivity">

          <div class="form-group padding-bottom-5">
            <label class="sr-only">Type</label>
              <select id="activity_type" class="form-control small" required>
                <?php foreach ($interactionlist as $row) { ?>
                  <option value="<?php echo $row['id'] ?>"><?php echo $row['name'] ?></option>
                <?php } ?>
              </select>
            <button type="submit" class="btn btn-success">Add</button>
          </div>

          <div class="form-group">
            <label class="sr-only">Time</label>
            <input type="text" id="activity_time" class="form-control" placeholder="Time, e.g. 40 of 0:40" required />
          </div>

          <div class="checkbox">
            <label>
              <input type="checkbox" id="start_timer" value="1" /> Start timer
            </label>
          </div>

        </form>

        <div class="leads list-group" id="activitylist">
          <?php $totaltime = 0; foreach ($interaction as $row) { $totaltime += $row['time']; ?>
            <a 
              class="list-group-item<?php if (!$row['end']) { echo ' list-group-item-warning'; } ?>"
              href="omat/<?php echo $project ?>/viewactivity/<?php echo $row['id'] ?>">
              <?php if (!$row['end']) { ?>
                <i class="fa fa-clock-o"></i> 
              <?php } else { ?>
                <span class="badge"><?php echo format_date("M d", $row['end']) ?></span>
              <?php } ?>
              <?php echo $row['name'] ?>
              <?php if ($row['end']) { ?>
              <span class="makesmall"><?php echo $row['time'] ?> min</span>
              <?php } else { ?><em class="makesmall">ongoing</em><?php } ?>
            </a>
          <?php } ?>
          <?php if (count($interaction)) { ?>
          <a
            class="list-group-item list-group-item-info"
            href="omat/<?php echo $project ?>/activityreport/<?php echo $id ?>/source"
          >
            <strong>Total time: 
            <?php echo formatTime($totaltime) ?>
            </strong>
          </a>
          <?php } ?>
        </div>

        <?php } ?>

      </div>

    </div>
  
  </div>

  <section>
    <h1>Manage Files</h1>

    <?php if (count($files)) { ?>

    <table class="table table-striped ellipsis">
      <tr>
        <th class="long">File</th>
        <th class="shorter">Website</th>
        <th class="short">Type</th>
        <th class="shorter">Uploaded</th>
        <th class="short">Actions</th>
      </tr>
    <?php foreach ($files as $row) { ?>
      <tr>
        <td>
        <?php if ($row['size']) { ?>
          <a href="omat/<?php echo $project ?>/download/<?php echo $row['id'] ?>">
            <?php echo $row['name'] ? $row['name'] : 'Download'; ?>
          </a>
        <?php } elseif ($row['url']) { ?>
          <a href="<?php echo $row['url'] ?>"><?php echo $row['name'] ? $row['name'] : 'Visit website' ?></a>
        <?php } else { ?>
          <?php echo $row['name'] ?>
        <?php } ?>
        </td>
        <td class="shorter">
          <?php if ($row['url'] && $row['size']) { ?>
            <a href="<?php echo $row['url'] ?>" title="Link to website"><i class="fa fa-link"></i></a>
          <?php } ?>
        </td>
        <td><?php echo $row['type'] ?></td>
        <td><?php echo format_date("M d, Y", $row['uploaded']) ?></td>
        <td>
        <a class="btn btn-primary" href="omat/<?php echo $project ?>/file/<?php echo $row['id'] ?>">Edit</a>
        <a class="btn btn-danger" href="omat/<?php echo $project ?>/viewsource/<?php echo $id ?>/deletefile/<?php echo $row['id'] ?>" onclick="javascript:return confirm('Are you sure?')">Delete</a></td>
      </tr>
    <?php } ?>
    </table>

    <?php } ?>

    <form method="post" class="form form-horizontal" enctype="multipart/form-data">

      <fieldset>
        <legend>Add File</legend>

        <div class="form-group">
          <label class="col-sm-2 control-label">Name</label>
          <div class="col-sm-10">
            <input class="form-control" type="text" name="name" value="<?php echo $fileinfo->name ?>" />
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-2 control-label">File</label>
          <div class="col-sm-10">
            <input class="" type="file" name="file" />
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-2 control-label">URL</label>
          <div class="col-sm-10">
            <input class="form-control" type="url" name="url" value="<?php echo $fileinfo->url ?>" />
          </div>
        </div>

        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary" name="fileform" value="true">Save</button>
          </div>
        </div>
        
      </fieldset>
    
    </form>


  </section>

  <a id="delete" href="omat/<?php echo $project ?>/viewsource/<?php echo $info->id ?>/delete" onclick="javascript:return confirm('Are you sure?')" class="btn btn-danger">Delete this source</a>

  <?php } ?>

<?php require_once 'include.footer.php'; ?>

  </body>
</html>
