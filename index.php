<?php
    require_once 'DbConnect.php';
    $Database = new DbConnect();
    $conn = $Database->dbConnection(); 
    $poster = 0;

    if(isset($_POST['dept_date'],$_POST['dept_time'],$_POST['from'],$_POST['to'],$_POST['submit'])){ 
        $poster = 1;
        $root = $_POST['from'];
        $goal = $_POST['to'];
        $mool = $root;          

        $date = $_POST['dept_date'];
        $time = $_POST['dept_time'];
        
        $query = $conn->query("SELECT * FROM city");
        $city_num = 0;
        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $city[$row['id']]['city'] = $row['city'];
            $city[$row['id']]['lat'] = $row['lat'];
            $city[$row['id']]['lng'] = $row['lng'];
            $city_num++;        
        }

        $query = $conn->query("SELECT node1,node2 FROM node ORDER BY distance");

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $graph[$row['node1']][] = $row['node2'];
            $graph[$row['node2']][] = $row['node1'];
        } 

        $solution = 0;
        global $temporary_buffer;

        for($i=0; $i<$city_num;$i++){
            $parent_node[$i] = -1;
        }


        /*
         * path finder
         */
        function Path($goal){
            global $parent_node;
            global $solution;
            global $temporary_buffer;

            if($parent_node[$goal]==-1){
                $temporary_buffer[$solution][]=$goal;
                return;
            }
            Path($parent_node[$goal]);
            $temporary_buffer[$solution][]=$goal;
        }

        /*
         * Simple Depth limited search
         */
        function DepthLimitedSearch($node, $goal, $depth, $parent){
            if($depth==0){
                if($node==$goal){
                    global $solution;
                    $solution++;
                    Path($goal);
                    return;
                }
                else return;
            }
            else if($depth>0){
                global $graph;
                global $mool;
                global $parent_node;
                $g_length = count($graph[$node]);

                for($i=0; $i<$g_length; $i++){
                    $val = $graph[$node][$i];

                    if($val==$mool || $val==$parent)
                        continue;
                    $parent_node[$val] = $node;
                    DepthLimitedSearch($val,$goal,$depth-1,$node);
                }
            }        
        }        

        for($i=1; $i<=2; $i++) {
            DepthLimitedSearch($root,$goal,$i,$root);
        }

        $number_of_path = 0;

        for($i=1; $i<=$solution; $i++){ 

            if($number_of_path>=3)
                break;

            $counter = count($temporary_buffer[$i]);

            if($counter==2){
                $from = $city[$temporary_buffer[$i][0]]['city'];
                $to = $city[$temporary_buffer[$i][1]]['city'];
                $query = $conn->query("SELECT * FROM bus WHERE (source = '$from' AND dest = '$to')
                        OR (source = '$to' AND dest = '$from') ORDER BY cost");

                while($row = $query->fetch(PDO::FETCH_ASSOC)){
                    $number_of_path++;
                    $path[$number_of_path][1]['way'] = $from.' to '.$to.' - '.$row['bus'];
                    $path[$number_of_path][1]['fare'] = $row['fare'];
                    $path[$number_of_path][1]['time'] = $row['time'];

                    if($number_of_path>=3)
                        break;
                }
            } elseif ($counter==3) {
                $from = $city[$temporary_buffer[$i][0]]['city'];
                $to = $city[$temporary_buffer[$i][1]]['city'];
                $query_one = $conn->query("SELECT * FROM bus WHERE (source = '$from' AND dest = '$to')
                        OR (source = '$to' AND dest = '$from') ORDER BY cost");

                while($row_one = $query_one->fetch(PDO::FETCH_ASSOC)){
                    if($number_of_path>=3)
                        break;

                    $temporary['way'] = $from.' to '.$to.' - '.$row_one['bus'];
                    $temporary['fare'] = $row_one['fare'];
                    $temporary['time'] = $row_one['time'];

                    $from2 = $city[$temporary_buffer[$i][1]]['city'];
                    $to2 = $city[$temporary_buffer[$i][2]]['city'];
                    $query_two = $conn->query("SELECT * FROM bus WHERE (source = '$from2' AND dest = '$to2')
                            OR (source = '$to2' AND dest = '$from2') ORDER BY cost");

                    while($row_two = $query_two->fetch(PDO::FETCH_ASSOC)){
                        $number_of_path++;

                        $path[$number_of_path][1]['way'] = $temporary['way'];
                        $path[$number_of_path][1]['fare'] = $temporary['fare'];
                        $path[$number_of_path][1]['time'] = $temporary['time'];

                        $path[$number_of_path][2]['way'] = $from2.' to '.$to2.' - '.$row_two['bus'];
                        $path[$number_of_path][2]['fare'] = $row_two['fare'];
                        $path[$number_of_path][2]['time'] = $row_two['time'];

                        if($number_of_path==3)
                            break;
                    }
                }                   
            }
        }
    }                  
    
?>


<html>
    <head>
        <link rel="stylesheet" href="style.css">
        <link href='https://fonts.googleapis.com/css?family=Josefin+Sans' rel='stylesheet' type='text/css'>
        
        <script src="http://maps.googleapis.com/maps/api/js"></script>
        <script>
        function initialize() {
          var mapProp = {
            center:new google.maps.LatLng(23.777176,90.399452),
            zoom:8,
            mapTypeId:google.maps.MapTypeId.ROADMAP
          };
          var map=new google.maps.Map(document.getElementById("googleMap"),mapProp);
        }
        google.maps.event.addDomListener(window, 'load', initialize);
        </script>
    </head>
    <p style="padding: 0mm;">
        <div id="wrapper">            
            <div class="wrapper-box">                
                <div class="box">
                <h2><a class="no-link" href="Finder.php">Intercity Route Finder</a></h2>   
                <!--hr class="hbasic"-->

                <form method="post">
                    <p style="color: white;"><b>Dept Date  </b><span style="color: #FF0000;">*</span><span style="color: #1AA9AC;">.</span> 
                    <input type="date" name="dept_date" <?php
                        if($poster==1){?>
                           value="<?php echo $date; ?>"
                       <?php } ?>required/>
                    <p style="color: white;"><b>Dept Time  </b><span style="color: #FF0000;">*</span><span style="color: #1AA9AC;">.</span> 
                    <select name="dept_time" required style="width: 182px;">
                        <option value="">
                            <?php if($poster==1){
                                    echo $time;
                                } else{                              
                                    echo 'Choose';
                                }
                            ?>
                        </option>
                        <option value="6 A.M.">6.00 A.M.</option>
                        <option value="7 A.M.">7.00 A.M.</option>
                        <option value="8 A.M.">8.00 A.M.</option>
                        <option value="9 A.M.">9.00 A.M.</option>
                        <option value="10 A.M.">10.00 A.M.</option>
                        <option value="11 A.M.">11.00 A.M.</option>
                        <option value="12 A.M.">12.00 P.M.</option>
                        <option value="1 P.M.">1.00 P.M.</option>
                        <option value="2 P.M.">2.00 P.M.</option>
                        <option value="3 P.M.">3.00 P.M.</option>
                        <option value="4 P.M.">4.00 P.M.</option>
                        <option value="5 P.M.">5.00 P.M.</option>
                        <option value="6 P.M.">6.00 P.M.</option>
                        <option value="7 P.M.">7.00 P.M.</option>
                        <option value="8P.M.">8.00 P.M.</option>
                        <option value="9 P.M.">9.00 P.M.</option>
                        <option value="10 P.M.">10.00 P.M.</option>    
                    </select>
                    <p style="color: white;"><b>Dept From </b><span style="color: #FF0000;">*</span>
                    <select name="from" required style="width: 182px;">
                        <option value="">
                            <?php if($poster==1){
                                    echo $city[$root]['city'];
                                } else{                              
                                    echo 'Choose';
                                }
                            ?>
                        </option>                   
                        <?php
                            $query = $conn->query("SELECT id,city FROM city ORDER BY city");
                            
                            while($row = $query->fetch(PDO::FETCH_ASSOC)){ ?>                                
                                <option value=<?php echo $row['id']; ?>><?php echo $row['city']; ?></option>
                        <?php } ?>                                                                                                          
                    </select>
                    <p style="color: white;"><b>Destination</b><span style="color: #FF0000;">*</span>
                    <select name="to" required style="width: 182px;">
                        <option value="">
                            <?php if($poster==1){
                                    echo $city[$goal]['city'];
                                } else{                              
                                    echo 'Choose';
                                }
                            ?>
                        </option>
                        <?php
                            $query = $conn->query("SELECT id,city FROM city ORDER BY city");
                            
                            while($row = $query->fetch(PDO::FETCH_ASSOC)){ ?>                                
                                <option value=<?php echo $row['id']; ?>><?php echo $row['city']; ?></option>
                        <?php } ?>              
                    </select>                 
                    <p style="padding: 5px;">            
                    <input type="submit" value="Submit" name="submit">
                </form>
                </div>
                
                <p style="padding: 0mm;">
                    
                <?php
                    if($poster==1){
                ?>
                <div class="xbox">                                         
                    <?php                        
                        for($i=1; $i<=$number_of_path; $i++){
                            $counter = count($path[$i]);
                            for($j=1; $j<=$counter; $j++){
                    ?>  
                        <div style="display:inline-block;vertical-align:top;">
                        <img src="<?php echo 'img/bus'.$i.'.png'; ?>" style="width:40px;" alt="img"/>
                        </div>
                        <div style="display:inline-block; color: #808080;">
                            <b><?php echo $path[$i][$j]['way']; ?></b>
                            <div>
                                <?php 
                                    echo $path[$i][$j]['time']/60;
                                    echo 'hrs';
                                    echo $path[$i][$j]['time']%60;
                                    echo 'min'; ?> &nbsp;&nbsp;&nbsp;
                                <span style="color: #FF69B4;">
                                    <?php echo '৳'.$path[$i][$j]['fare']; ?></span>
                            </div>
                        </div>
                    
                            <?php if($j+1<=$counter) { ?>
                                <div class="<?php echo 'verticalLine'.$i; ?>"></div>
                            <?php } ?>
                        <?php } 
                            if($i+1<=$number_of_path) {
                        ?>
                            <hr>
                        <?php } ?>
                    <?php } ?>
                    
                </div>
            </div>
                                    
            <div id="googleMap" style="height:150%;"></div>
            <?php } else{ ?>    
            </div>
                                    
            <div id="googleMap" style="height:150%;"></div>
            <?php } ?>
            
        </div>
</html>

