<?php
$servername = "localhost";
$username = "274693";
$password = "FeG9JTMZ";
$dbname = "274693";

$conn = mysqli_connect($servername, $username, $password, $dbname); //Kobler til databasen 

//Dersom denne feilmld ikke kommer opp, er tilkoblingen suksessfull
if(!$conn)
{
    die("Connection failed: " . mysqli_connect_error());
}

//Benytter Prepared Statement in MySQLi for alle if-setningene

//Henter informasjon skrevet i popup-form og insert inn i tabellen lagd i mysql
//isset() sjekker om en variabel er deklarert og ikke NULL 
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task']) && isset($_POST['description']))
{
    $task = $_POST['task'];
    $description = $_POST['description'];

    //? --> verdien blir substituert med brukererns verdier gjennom knapper og skjema 
    $stmt = $conn->prepare("INSERT INTO ToDo (Task, Description, Status) VALUES (?, ?, 'Not done')");
    //"ss" --> type data til parameterne, s er string og forteller mysql denne informasjonen
    $stmt->bind_param("ss", $task, $description);
    $stmt->execute();
    $stmt->close();

}

//Oppdaterer status dersom bruker endrer i nedtrykksmeny
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status']) && isset($_POST['id']))
{
    $status = $_POST['status'];
    $task_id = $_POST['id'];

    $stmt = $conn->prepare("UPDATE ToDo SET Status=? WHERE Task=?");
    $stmt->bind_param("ss", $status, $task_id);
    $stmt->execute();
    $stmt->close();
}


//Dersom raden blir slettet på nettsiden blir den også slettet i datasystemet
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_row']))
{
    $delete_task= $_POST['delete_row'];

    $stmt = $conn->prepare("DELETE FROM ToDo WHERE Task=?");
    $stmt->bind_param("s", $delete_task);
    $stmt->execute();
    $stmt->close();
}

//Dersom raden blir oppdatert på nettsiden blir den også oppdatert i datasystemet
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_row']) && isset($_POST['update_task']) && isset($_POST['update_description']))
{
    $original_task = $_POST['update_row'];
    $update_task = $_POST['update_task'];
    $update_description = $_POST['update_description'];

    $stmt = $conn->prepare("UPDATE ToDo SET Task=?, Description=? WHERE Task=?");
    $stmt->bind_param("sss", $update_task, $update_description, $original_task);
    $stmt->execute();
    $stmt->close(); 
}

$sql = "SELECT * FROM ToDo";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
   <head>
    <title>ToDo list</title>
    <link rel="stylesheet" href="./todolist.css?v=1">
   </head>

   <body>
    <h3>ToDo List: Fyll ut dine gjøremål!</h3>
            <?php
            //Etablerer en tabell og setter inn alle kolonnene med tittel
                echo "<table>";
                echo "<thead><tr><th>Task</th><th>Description</th><th>Status</th><th>Action</th></tr></thead>";
                echo "<tbody>";
                //fetch_assoc() henter resulterende rad til $result som utfører spørringen til databasen min
                while($row = mysqli_fetch_assoc($result))
                {
                    echo "<tr>";
                    //$row[] gir tilgang til verdiene i de kolonnene skrevet i klammeparentes 
                    echo "<td>" . $row["Task"]. "</td>";
                    echo "<td>" . $row["Description"] . "</td>";
                    
                    echo "<td>";
                    echo "<form action='' method='post'>";
                    echo "<input type='hidden' name='id' value='" . $row["Task"] . "'>";

                    /*Benytter JS kode 'this.form.submit()' med automatisk håndtering
                    istetdet for å lage en egen knapp for å sende inn Status*/
                    echo "<select name='status' onchange='this.form.submit()'>";

                    //Koder to if-setninger med svaralternativene for Status, der defalut har blitt satt til 'Not done'
                    if($row["Status"]=="Not done"){
                        echo "<option value='Not done' selected>Not done</option>";
                    }else{
                        echo "<option value='Not done'>Not done</option>";
                    }

                    if(($row["Status"]=="Done")){
                        echo "<option value='Done' selected>Done</option>";
                    }else{
                        echo "<option value='Done'>Done</option>";
                    }

                    echo "</select>";
                    echo "</form>";
                    echo "</td>";
                    
                    //Delete knappen fungerer sammen med if setningen laget lenger opp
                    echo "<td>";
                    echo "<form action='' method='post'>";
                    //name='delete_row' går inn i if setning og gjennomfører $stmt
                    echo "<input type='hidden' name='delete_row' value='" . $row["Task"] . "'>";
                    echo "<button type='submit'>Delete</button>";
                    echo "</form>";
                    
                    //Edit knappen fungerer sammen med if setningen laget lenger opp
                    //I edit knappen kan både task og description bli redigert, dermed sier vi knappen blir trykket på, skal javascript koden ta inn informasjon fra de to kolonnene og returnere det som blir skrevet av bruker
                    echo "<button onclick='popUpEditForm(\"" . $row["Task"] . "\", \"" . $row["Description"] . "\")'>Edit</button>";
                    echo "</td>";

                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
            ?>

        <!--Lager en skjult popup for knappen Edit !-->
        <div class="edit-popup" id="editForm">
            <form action="" method="post" class="edit-container">
               <h2>Edit task</h2>
                <input type="hidden" name="update_row" id="editId">

               <label for="editTask"><b>Edit task</b></label>
               <input type="text" name="update_task" id="editTask"required>

               <label for="editDescription"><b>Edit description</b></label>
               <input type="text" name="update_description" id="editDescription" required>

               <button type="submit" class="btn submitEdit">Submit edit</button>
               <button type="button" class="btn cancelEdit" onclick="closeEditForm()">Cancel edit</button>
        </form> 
        </div>

        <!--Lager en skjult popup for knappen Add new task !-->
        <button class="open-button" onclick="openForm()">Add new task</button>
        <div class="form-popup" id="myForm">
            <form action="" method="post" class="form-container">
                <h1>New task</h1>
                
                <label for="task"><b>Task</b></label>
                <input type="text" placeholder="Enter action" name="task" required>

                <label for="description"><b>Description</b></label>
                <input type="text" placeholder="Enter description" name="description" required>

                <button type="submit" class="btn submit">Register new task</button>
                <button type="button" class="btn cancel" onclick="closeForm()">Cancel</button>
            </form>
        </div>


    <script>
        function openForm(){
        //"block" --> elementet med id-en definert i funksjonen oppfører seg som et blokk element, og synliggjør det som har display: none; (css)
            document.getElementById("myForm").style.display = "block";
        }

        function closeForm() {
        //"none" --> knappen Cancel vil stenge skjemaet, og id-en til hele skjemaet sier at elementet ikke skal være synlig når funksjonen blir kalt opp
            document.getElementById("myForm").style.display = "none";
        }

        function popUpEditForm(taskName, taskDescription){
            document.getElementById("editId").value = taskName;
            document.getElementById("editTask").value = taskName;
            document.getElementById("editDescription").value = taskDescription;
            document.getElementById("editForm").style.display = "block";
        }
        

        function closeEditForm(){
            document.getElementById("editForm").style.display = "none";
        }

    </script>
    <?php 
    //Stenger database tilkoblingen
    mysqli_close($conn); 
    ?>
   </body>
</html>
