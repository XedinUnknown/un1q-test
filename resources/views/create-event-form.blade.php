<form action="{{ route('events.store') }}" method="post">
    <table>
        <tr>
            <th><label for="start"><?= __('Start') ?></label></th>
            <td><input type="text" name="start" id="start" placeholder="Y-m-dTH:i:s" /></td>
        </tr>
        <tr>
            <th><label for="end"><?= __('End') ?></label></th>
            <td><input type="text" name="end" id="end" placeholder="Y-m-dTH:i:s" /></td>
        </tr>
        <tr>
            <th><label for="frequency"><?= __('Frequency') ?></label></th>
            <td>
                <select name="frequency" id="frequency" >
                    @foreach($frequencies as $freq)
                        <option value="{{ $freq }}">{{ $freq }}</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="interval"><?= __('Interval') ?></label></th>
            <td><input type="number" name="interval" id="interval" min="1" step="1" /></td>
        </tr>
        <tr>
            <th><label for="until"><?= __('Until') ?></label></th>
            <td><input type="text" name="until" id="until" placeholder="Y-m-dTH:i:s" /></td>
        </tr>
        <tr>
            <td colspan="2"><input type="submit" name="submit" id="submit" value="Create" /></td>
        </tr>
    </table>
</form>
