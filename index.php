<?php
// Inclure la connexion à la base de données
include 'db_connect.php';

// Récupérer tous les étudiants
$stmt = $conn->prepare("SELECT * FROM student");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les classes uniques
$stmtClasses = $conn->prepare("SELECT DISTINCT class FROM student");
$stmtClasses->execute();
$classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les types de bourse (valu) uniques
$stmtValu = $conn->prepare("SELECT DISTINCT valu FROM student");
$stmtValu->execute();
$valus = $stmtValu->fetchAll(PDO::FETCH_ASSOC);

// Compter les étudiants validés et non validés
$stmtValid = $conn->prepare("SELECT COUNT(*) AS count_valid FROM student WHERE valide = 1");
$stmtValid->execute();
$countValid = $stmtValid->fetch(PDO::FETCH_ASSOC)['count_valid'];

$stmtNonValid = $conn->prepare("SELECT COUNT(*) AS count_non_valid FROM student WHERE valide = 0");
$stmtNonValid->execute();
$countNonValid = $stmtNonValid->fetch(PDO::FETCH_ASSOC)['count_non_valid'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التلاميذ</title>

    <!-- Inclure Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        .checked {
            background-color: #d4edda; /* Vert pâle pour les étudiants validés */
        }
        body {
            direction: rtl;
        }
    </style>

    <script>
        function searchStudents() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let filterClass = document.getElementById("classFilter").value;
            let filterValu = document.getElementById("valuFilter").value;
            let filterValid = document.getElementById("filterValid").checked;
            let filterNonValid = document.getElementById("filterNonValid").checked;
            let tableRows = document.getElementsByTagName("tr");

            for (let i = 1; i < tableRows.length; i++) {
                let nom = tableRows[i].getElementsByTagName("td")[0].innerText.toLowerCase();
                let className = tableRows[i].getElementsByTagName("td")[2].innerText;
                let valu = tableRows[i].getElementsByTagName("td")[3].innerText;
                let isValid = tableRows[i].getElementsByTagName("td")[1].getElementsByTagName("input")[0].checked;

                let matchesName = nom.includes(input);
                let matchesClass = (filterClass === "" || className === filterClass);
                let matchesValu = (filterValu === "" || valu === filterValu);
                let matchesValidation = (
                    (!filterValid && !filterNonValid) || 
                    (filterValid && isValid) || 
                    (filterNonValid && !isValid)
                );

                if (matchesName && matchesClass && matchesValu && matchesValidation) {
                    tableRows[i].style.display = "";
                } else {
                    tableRows[i].style.display = "none";
                }
            }
        }

        function toggleStatus(id, checkbox) {
            let xhr = new XMLHttpRequest();
            xhr.open("POST", "update_status.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    let response = JSON.parse(xhr.responseText);
                    let row = document.getElementById("row-" + id);
                    if (checkbox.checked) {
                        row.classList.add("checked");
                    } else {
                        row.classList.remove("checked");
                    }

                    document.getElementById("countValid").innerText = response.count_valid;
                    document.getElementById("countNonValid").innerText = response.count_non_valid;

                    searchStudents();
                }
            };
            xhr.send("id=" + id + "&valide=" + (checkbox.checked ? 1 : 0));
        }
    </script>
</head>

<body class="container ">
<?php include("nav.php") ?>
<div class="row">
<h1 class="mb-4 col-6">التلاميذ</h1>

<div class="col-6">
    <button class="btn btn-warning" id="resetButton" onclick="resetValidStatus()">إعادة تعيين الحضور</button>
</div>

</div>

    <div class="row mb-3">
        <div class="row text-center">
            <p class="col-6">الحاضرون : <span id="countValid" class="badge bg-success"><?= $countValid ?></span></p>
            <p class="col-6">الباقون : <span id="countNonValid" class="badge bg-danger"><?= $countNonValid ?></span></p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
        <label for="searchInput" class="form-label">الاسم</label>
            <input type="text" id="searchInput" class="form-control" onkeyup="searchStudents()" placeholder="ابحث عبر الاسم.">
        </div>

        <!-- Select pour filtrer par classe -->
        <div class="col-6">
            <label for="classFilter" class="form-label">القسم</label>
            <select id="classFilter" class="form-select" onchange="searchStudents()">
                <option value="">الكل</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= htmlspecialchars($class['class']) ?>"><?= htmlspecialchars($class['class']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Select pour filtrer par valu -->
        <div class="col-6">
            <label for="valuFilter" class="form-label">المنحة</label>
            <select id="valuFilter" class="form-select" onchange="searchStudents()">
                <option value="">الكل</option>
                <?php foreach ($valus as $valu): ?>
                    <option value="<?= htmlspecialchars($valu['valu']) ?>"><?= htmlspecialchars($valu['valu']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Checkboxes pour filtrer par validation -->
     <div style="display: flex;
    justify-content: space-evenly;">
    <div class="form-check ">
        <label class="form-check-label" for="filterValid">الحاضرون</label>
        <input type="checkbox" class="form-check-input" id="filterValid" onclick="searchStudents()">

    </div>
    <div class="form-check">
    <label class="form-check-label" for="filterNonValid">الباقون</label>
        <input type="checkbox" class="form-check-input" id="filterNonValid" onclick="searchStudents()">
    </div>
    </div>

    <table class="table table-striped table-bordered mt-4">
    <thead class="table-dark">
        <tr>
            <th>الاسم</th>
            <th>الحضور</th>
            <th>القسم</th>
            <th>نوع المنحة</th>
            <th>تعديل</th> <!-- Colonne pour modifier -->
            <th>حذف</th> <!-- Colonne pour supprimer -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $student): ?>
        <tr id="row-<?= $student['id'] ?>" class="<?= $student['valide'] ? 'checked' : '' ?>">
            <td><?= htmlspecialchars($student['nom']) ?></td>
            <td>
                <input type="checkbox" <?= $student['valide'] ? 'checked' : '' ?> 
                onclick="toggleStatus(<?= $student['id'] ?>, this)">
            </td>
            <td><?= htmlspecialchars($student['class']) ?></td>
            <td><?= htmlspecialchars($student['valu']) ?></td>

            <!-- Bouton Modifier -->
            <td>
                <a href="edit_student.php?id=<?= $student['id'] ?>" class="btn btn-primary btn-sm">تعديل</a>
            </td>

            <!-- Bouton Supprimer avec confirmation JavaScript -->
            <td>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $student['id'] ?>)">حذف</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


    <!-- Inclure Bootstrap JS et Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-kaV1Z1z5m8bHQrleJvwC6Eg6sjfnIQebC3HJ+p5Y5S3mI7s5M10d5rLjLNHhB4bj" crossorigin="anonymous"></script>
<script>
 function confirmDelete(id) {
        if (confirm("هل أنت متأكد أنك تريد حذف هذا التلميذ؟")) {
            window.location.href = "delete_student.php?id=" + id;
        }
    }

    function resetValidStatus() {
    if (confirm("هل أنت متأكد أنك  اعادة تعيين الحضور?")) {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "reset_valid_status.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                let response = JSON.parse(xhr.responseText);
                alert(response.message); // Show success message
                // Reload the page or update the UI as needed
                location.reload(); // Reload to see the changes
            }
        };
        xhr.send();
    }
}

    </script>
</body>
</html>
