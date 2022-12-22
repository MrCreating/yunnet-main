<!DOCTYPE html>
  <html lang="ru">
    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <title>Ведомости</title>
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <link type="text/css" rel="stylesheet" href="/vd_sources/css/materialize.css" media="screen,projection"/>
      <script type="text/javascript" src="/vd_sources/js/materialize.js"></script>
      <script type="text/javascript" src="/vd_sources/js/schedule.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    </head>

    <body>
      <style>
        html, body
        {
          background-color: #F2F2F2;
        }

        .card-panel
        {
          width: 450px;
          margin: 15px auto;
        }   
      </style>

      <div>                                                                 <!--      Белая панелька сверху           -->
          <?php require __DIR__ . '/components/sidenav.php'; ?>
      </div>

      <?php $subject_id = (int) \unt\objects\Request::get()->data['id']; ?>
      <?php $group_id   = (int) \unt\objects\Request::get()->data['g_id']; ?>

      <?php if ($subject_id === 0): ?>
          <div>
              <ul class="card collection with-header" style="width: 100%">
                  <li style="padding-left: 30px !important;" class="collection-header">
                      <h6><b>Ведомости по предметам</b></h6>
                  </li>

                  <?php $subjects = get_subjects_list(); ?>
                  <?php if (count($subjects) <= 0): ?>
                      <div style="padding: 20px">Список групп пуст.</div>
                  <?php else: ?>
                      <?php foreach ($subjects as $subject): ?>
                          <li class="collection-item">
                              <a style="color: black" href="/sheet?id=<?php echo $subject['id'] ?>"><?php echo htmlspecialchars($subject['title']); ?><div class="secondary-content"><i class="material-icons" style="color: #7F1E2F">arrow_forward</i></div></a>
                          </li>
                      <?php endforeach; ?>
                  <?php endif; ?>
              </ul>
          </div>
      <?php else: ?>
          <div>
              <ul class="card collection with-header valign-wrapper" style="width: 100%">
                  <li class="collection-header valign-wrapper">
                      <a href="/sheet" style="width: 20px; height: 20px; margin-right: 15px"><i class="material-icons" style="color: #7F1E2F">arrow_backward</i></a>
                      <h6><b>Ведомости по группам для предмета: <?php echo get_subject_info($subject_id)['title']; ?></b></h6>
                  </li>

<!--                  --><?php //$subjects = get_subjects_list(); ?>
<!--                  --><?php //if (count($subjects) <= 0): ?>
<!--                      <div style="padding: 20px">Список групп пуст.</div>-->
<!--                  --><?php //else: ?>
<!--                      --><?php //foreach ($subjects as $subject): ?>
<!--                          <li class="collection-item">-->
<!--                              <a style="color: black" href="/sheet?id=--><?php //echo $subject['id'] ?><!--">--><?php //echo htmlspecialchars($subject['title']); ?><!--<div class="secondary-content"><i class="material-icons" style="color: #7F1E2F">arrow_forward</i></div></a>-->
<!--                          </li>-->
<!--                      --><?php //endforeach; ?>
<!--                  --><?php //endif; ?>
              </ul>
          </div>
      <?php endif; ?>
    </body>
  </html>