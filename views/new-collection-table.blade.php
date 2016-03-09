<div class="table-responsive">
    <table class="{{isset($table_class) ? $table_class : 'table table-bordered table-striped table-condensed mb-none'}}">
        <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ $data->id }}</td>
            <td>{{ $data->title }}</td>
        </tr>
        </tbody>
    </table>
</div>
