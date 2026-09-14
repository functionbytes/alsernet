<form action="{$link->getAdminLink('AdminModules')}&configure={$module_name}&controller=addEvent" method="post">
    <label for="title">Event Title</label>
    <input type="text" name="title" id="title" required>

    <label for="start_date">Start Date</label>
    <input type="datetime-local" name="start_date" id="start_date" required>

    <label for="end_date">End Date</label>
    <input type="datetime-local" name="end_date" id="end_date" required>

    <label for="available">Available</label>
    <input type="checkbox" name="available" id="available" value="1">

    <button type="submit" name="submit_add_event" class="btn btn-default">Add Event</button>
</form>