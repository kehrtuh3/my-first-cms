<?php include "templates/include/header.php" ?>
<?php include "templates/admin/include/header.php" ?>
<?php $subcategory = $results['subcategory']; ?>
    <h1><?php echo $results['pageTitle'] ?></h1>

    <form action="admin.php?action=<?php echo $results['formAction'] ?>" method="post">
        <input type="hidden" name="subcategoryId" value="<?php echo $subcategory->id ?>"/>

    <?php if (isset($results['errorMessage'])) { ?><div class="errorMessage"><?php echo $results['errorMessage'] ?></div><?php } ?>

        <ul>
            <li>
                <label for="name">Subcategory Name</label>
                <input type="text" name="name" id="name" required autofocus maxlength="255"
                       value="<?php echo htmlspecialchars((string) $subcategory->name) ?>" />
            </li>
            <li>
                <label for="categoryId">Category</label>
                <select name="categoryId" id="categoryId" required>
                    <option value=""<?php echo $subcategory->categoryId ? '' : ' selected' ?>>Не выбрана</option>
                <?php foreach ($results['categories'] as $category) { ?>
                    <option value="<?php echo $category->id ?>"<?php echo ($category->id == $subcategory->categoryId) ? ' selected' : '' ?>><?php echo htmlspecialchars($category->name) ?></option>
                <?php } ?>
                </select>
            </li>
        </ul>

        <div class="buttons">
            <input type="submit" name="saveChanges" value="Save Changes" />
            <input type="submit" formnovalidate name="cancel" value="Cancel" />
        </div>
    </form>

    <?php if ($subcategory->id) { ?>
        <p><a href="admin.php?action=deleteSubcategory&amp;subcategoryId=<?php echo $subcategory->id ?>" onclick="return confirm('Delete This Subcategory?')">Delete This Subcategory</a></p>
    <?php } ?>
<?php include "templates/include/footer.php" ?>