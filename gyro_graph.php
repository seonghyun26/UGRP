<?php
  header('Content-Type: text/html; charset=utf-8');
  include('conn.php');  
  
  $kickboard = $_POST['kickboard'];
  if(empty($kickboard)){
    $kickboard = $_COOKIE['kickboard'];
    if(empty($kickboard))  $kickboard = '120';
  }
  setcookie("kickboard", $kickboard, time() + 86400);
  
  $date = $_POST['date'];
  if(empty($date)) {
    $date = $_COOKIE['date'];
    if(empty($date))  $date = date("Y-m-d");
  }
  setcookie("date", $date, time() + 86400);
  
  $start_time = $_POST['start_time'];
  if(empty($start_time)) {
    $start_time = $_COOKIE['start_time'];
    if(empty($start_time))  $start_time = date("H:00:00", strtotime("-1 hour"));
  }
  $mysql_start_date = DateTime::createFromFormat('Y-m-d H:i:s', $date. $start_time)->format('Y-m-d H:i:s');
  setcookie("start_time", $start_time, time() + 86400);

  $end_time = $_POST['end_time'];
  if(empty($end_time)) {
    $end_time = $_COOKIE['end_time'];
    if(empty($end_time))  $end_time = date("H:00:00", strtotime("-50 minute"));
  }
  $mysql_end_date = DateTime::createFromFormat("Y-m-d H:i:s", $date. $end_time)->format('Y-m-d H:i:s');
  setcookie("end_time", $end_time, time() + 86400);
  
  $range = $_POST['range'];
  if(empty($range)){
    $range = $_COOKIE['range'];
    if(empty($range)) $range = 24;
  }
  setcookie("range", $range, time() + 86400);

  if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
  } else {
    $sql = "SELECT * FROM mark2 WHERE kickboard = '$kickboard' AND (record_date BETWEEN '$mysql_start_date' AND '$mysql_end_date')";
    $result = mysqli_query($conn, $sql);
    $num = mysqli_num_rows($result);
    $result_date = array();
    $deg_x = array();
    $deg_y = array();
    $deg_z = array();
    $agv_x = array();
    $agv_y = array();
    $agv_z = array();
    $acc_x = array();
    $acc_y = array();
    $acc_z = array();
    
    while($row = mysqli_fetch_array($result)) {
      array_push($result_date, $row['record_date']);
      array_push($deg_x, $row['deg_x']);
      array_push($deg_y, $row['deg_y']);
      array_push($deg_z, $row['deg_z']);
      $deg_x_avg += $rows['deg_x']/$num;
      
      array_push($agv_x, $row['agv_x']);
      array_push($agv_y, $row['agv_y']);
      array_push($agv_z, $row['agv_z']);
      array_push($acc_x, $row['acc_x']);
      array_push($acc_y, $row['acc_y']);
      array_push($acc_z, $row['acc_z']);
    }
  }

  $z_std_dev = array();
  for($i = 0 ; $i < $range ; $i++){
    array_push($z_std_dev, 0);
  }

  for ( $i = 0 ; $i < $num ; $i++ ){
    $avg_z = 0;
    for( $j = $i ; $j < $i + $range ; $j++) {
      $avg_z += $acc_z[$j] / $range ;
    }
    $temp = 0;
    for( $j = $i ; $j < $i + $range ; $j++) {
      $temp += round(pow( ($avg_z - $acc_z[$j]), 2) / $range, 3);
    }
    $temp = sqrt($temp);
    array_push($z_std_dev, $temp);
  }
?>

<!DOCTYPE html>
<html style="font-size: 16px;">
  <title>Gyro Graph</title>

  <head>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/button.css">
    <style type="text/css">
      .container {
          width: 80%;
          margin: 15px auto;
      }
    </style>
  </head>

  <body>
    <header>
      <?php include('gyro_button.html'); ?>
      <br><br>
      <?php include('gyro_search.html')?>
    </header>  

    <!-- <p><?php
      echo "Searched : ".$kickboard.
          " from ".date($mysql_start_date).
          " ~ ".date($mysql_end_date);
    ?></p> -->

    <div class="container">
      <details>
        <summary align="center">Degree Chart</summary>
        <p><canvas id="Degree_Chart" width="1000" height="500"></canvas></p>
      </details>  
      <br>
      <details>
        <summary align="center">Angular Velocity Chart</summary>
        <p><canvas id="agv_Chart" width="1000" height="500"></canvas></p>
      </details>
      <br>
      <canvas id="acc_Chart" width="1000" height="500"></canvas>
    </div>
      
    <div align="center">
      <hr size="4px" color="darkcyan" width="80%">
      <br>
      <form method="POST" action="download.php">
        <input class="button demo" type='submit' value='Download'>
      </form>
      <br>
    </div>

    <!-- Degree GRAPH -->
    <script>
      var ctx = document.getElementById("Degree_Chart");
      var myChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: [<?php 
            for($i = 0 ; $i < count($result_date) ; $i++){
              echo '"'.$result_date[$i].'",';
            }
            ?>],
          datasets: [
            {
              label: 'Degree X',
              data: [<?php
                for($i = 0 ; $i < count($deg_x) ; $i++){
                  echo '"'.$deg_x[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(255, 0, 0, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }, {
              label: 'Degree Y',
              data: [<?php
                for($i = 0 ; $i < count($deg_y) ; $i++){
                  echo '"'.$deg_y[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [  
                'rgba(0, 255, 0, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }, {
              label: 'Degree Z',
              data: [<?php
                for($i = 0 ; $i < count($deg_z) ; $i++){
                  echo '"'.$deg_z[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(0, 0, 255, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }
          ]
        },
        options: {
          hover: {mode: null},
          responsive: true,
          scales: {
            xAxes: [{
              display: true,
              scaleLabel: {
                display: true,
                labelString: 'Date',
                fontSize: '16',
              }
            }],
            yAxes: [{
              ticks: {
                  beginAtZero: true
              },
              display: true,
              scaleLabel: {
                display: true,
                labelString: 'Degree °',
                fontSize: '16'
              }
            }]
          }
        }
      });
    </script>
    
    <!-- Angular Velocity GRAPH -->
    <script>
      var ctx = document.getElementById("agv_Chart");
      var myChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: [<?php 
            for($i = 0 ; $i < count($result_date) ; $i++){
              echo '"'.$result_date[$i].'",';
            }
            ?>],
          datasets: [
            {
              label: 'Angular Velocity X',
              data: [<?php
                for($i = 0 ; $i < count($agv_x) ; $i++){
                  echo '"'.$agv_x[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(255, 0, 0, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }, {
              label: 'Angular Velocity Y',
              data: [<?php
                for($i = 0 ; $i < count($agv_y) ; $i++){
                  echo '"'.$agv_y[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(0, 255, 0, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }, {
              label: 'Angular Velocity Z',
              data: [<?php
                for($i = 0 ; $i < count($agv_z) ; $i++){
                  echo '"'.$agv_z[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(0, 0, 255, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }
          ]
        },
        options: {
          hover: {mode: null},
          responsive: true,
          scales: {
            xAxes: [{
              display: true,
              scaleLabel: {
                display: true,
                labelString: 'Date',
                fontSize: '16',
              }
            }],
            yAxes: [{
              ticks: {
                  beginAtZero: true
              },
              display: true,
              scaleLabel: {
                display: true,
                labelString: '[°/s]',
                fontSize: '16'
              }
            }]
          }
        }
      });
    </script>

    <!-- Acceleration GRAPH -->
    <script>
      var ctx = document.getElementById("acc_Chart");
      var myChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: [<?php 
            for($i = 0 ; $i < count($result_date) ; $i++){
              echo '"'.$result_date[$i].'",';
            }
            ?>],
          datasets: [
            {
              label: 'Acceleration X',
              data: [<?php
                for($i = 0 ; $i < count($acc_x) ; $i++){
                  echo '"'.$acc_x[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(255, 0, 0, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }, {
              label: 'Acceleration Y',
              data: [<?php
                for($i = 0 ; $i < count($acc_y) ; $i++){
                  echo '"'.$acc_y[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(0, 255, 0, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }, {
              label: 'Acceleration Z',
              data: [<?php
                for($i = 0 ; $i < count($acc_z) ; $i++){
                  echo '"'.$acc_z[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(0, 0, 255, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }, {
              label: 'Z standard Deviation',
              data: [<?php
                for($i = 0 ; $i < $num ; $i++){
                  echo '"'.$z_std_dev[$i].'",';
                }
              ?>],
              backgroundColor: [
                'rgba(255, 255, 255, 0.2)',
              ],
              borderColor: [
                'rgba(255, 0, 255, 0.6)',
              ],
              borderWidth: 1,
              pointRadius: 0
            }
          ]
        },
        options: {
          hover: {mode: null},
          responsive: true,
          scales: {
            xAxes: [{
              display: true,
              scaleLabel: {
                display: true,
                labelString: 'Date',
                fontSize: '16',
              }
            }],
            yAxes: [{
              ticks: {
                  beginAtZero: true
              },
              display: true,
              scaleLabel: {
                display: true,
                labelString: '[g]',
                fontSize: '16'
              }
            }]
          }
        }
      });
    </script>

  </body>

</html>