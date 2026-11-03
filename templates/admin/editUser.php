<?php include "templates/include/header.php" ?>
<?php include "templates/admin/include/header.php" ?>
<?php $user = $results['user']; ?>
    <h1><?php echo $results['pageTitle'] ?></h1>

    <form action="admin.php?action=<?php echo $results['formAction'] ?>" method="post">
        <input type="hidden" name="userId" value="<?php echo $user->id ?>"/>

    <?php if (isset($results['errorMessage'])) { ?><div class="errorMessage"><?php echo $results['errorMessage'] ?></div><?php } ?>

        <ul>
            <li>
                <label for="login">Login</label>
                <input type="text" name="login" id="login" required autofocus maxlength="50"
                       value="<?php echo htmlspecialchars((string) $user->login) ?>" />
            </li>
            <li>
                <label for="password">Password</label>
                <input type="password" name="password" id="password" maxlength="72" autocomplete="new-password"
                       placeholder="<?php echo $user->id ? 'Оставьте пустым, чтобы не менять' : 'Password' ?>"
                       <?php echo $user->id ? '' : 'required' ?> />
            </li>
            <li>
                <label for="active">Активен (может входить)</label>
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" id="active" value="1" <?php echo $user->active ? 'checked' : '' ?>>
            </li>
        </ul>

        <div class="buttons">
            <input type="submit" name="saveChanges" value="Save Changes" />
            <input type="submit" formnovalidate name="cancel" value="Cancel" />
        </div>
    </form>

    <?php if ($user->id) { ?>
        <p><a href="admin.php?action=deleteUser&amp;userId=<?php echo $user->id ?>" onclick="return confirm('Delete This User?')">Delete This User</a></p>
    <?php } ?>
<?php include "templates/include/footer.php" ?>