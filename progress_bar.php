<?php
function showProgress($raised,$target){

    $percent = 0;

    if($target > 0){
        $percent = ($raised/$target)*100;

        if($percent > 100){
            $percent = 100;
        }
    }

    echo "

    <style>
    .progress{
        width:100%;
        background:#e9ecef;
        border-radius:20px;
        overflow:hidden;
        margin:15px 0;
        height:25px;
    }

    .progress-bar{
        height:100%;
        background:linear-gradient(45deg,#28a745,#20c997);
        display:flex;
        align-items:center;
        justify-content:center;
        transition:0.5s ease;
    }

    .progress-text{
        color:white;
        font-size:14px;
        font-weight:bold;
    }
    </style>

    <div class='progress'>
        <div class='progress-bar' style='width:$percent%'>
            <span class='progress-text'>".round($percent)."%</span>
        </div>
    </div>
    ";
}
?>