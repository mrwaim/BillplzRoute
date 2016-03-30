@extends('app')

@section('page-header')
    @include('elements.page-header', ['section_title' => 'Billplz', 'page_title' => 'New Collection'])
@endsection

@section('content')
    <section class="panel">
        <header class="panel-heading">
            <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div>
            <h2 class="panel-title">New Collection</h2>
        </header>
        <div class="panel-body">
            @include('billplz-route::new-collection-table')
        </div>
    </section>
@endsection