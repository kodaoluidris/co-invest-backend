<?php
use App\Models\User;


function validateUserById($id, $columns=null)
{
    $clmns = $columns == null ? '*' : implode(',',$columns);
    $query = User::where('id', $id)->select($clmns)->first();
    return $query ?? 'notFound';
}