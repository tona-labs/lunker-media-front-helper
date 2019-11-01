<div class="form-group">
    <label for="name">Name</label>
    <input type="text" class="form-control" id="name" name="name" placeholder="Name" required="">
</div>
<div class="form-group">
    <label for="email">Email Address</label>
    <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" placeholder="Enter email" required="">
</div>
<div class="form-group">
    <label for="phone">Telephone</label>
    <input type="tel" class="form-control" id="phone" name="phone" placeholder="1-555-123-4567" required="">
</div>
<div class="controls">
    <input type="hidden" id="travelers" name="travelers" value="<?= $_POST['travelers'] ?>">
    <input type="hidden" name="date" value="<?= $_POST['date'] ?>">
</div>