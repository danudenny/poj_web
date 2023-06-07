<template>
    <form class="card">
        <div class="card-header ">
            <h4 class="card-title mb-0">Edit Role</h4>
            <div class="card-options"><a class="card-options-collapse" href="javascript:void(0)"
                    data-bs-toggle="card-collapse"><i class="fe fe-chevron-up"></i></a><a class="card-options-remove"
                    href="javascript:void(0)" data-bs-toggle="card-remove"><i class="fe fe-x"></i></a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6 col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input class="form-control" type="text" placeholder="Name" value="Super Admin">
                    </div>
                </div>
            </div>
            <dv class="row">
                <div class="col-sm-6">
                    <div class="card-block row">
                        <div class="col-sm-12 col-lg-12 col-xl-12">
                            <div class="table-responsive">
                            <table class="table">
                                <thead class="table bg-primary">
                                    <tr>
                                        <th scope="col">
                                            <input class="form-check-input checkbox-solid-light" id="solid2" type="checkbox">
                                        </th>
                                        <th scope="col">Permission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th scope="row"><input class="form-check-input checkbox-solid-light" id="solid2" type="checkbox"></th>
                                        <td>manage.users</td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><input class="form-check-input checkbox-solid-light" id="solid2" type="checkbox"></th>
                                        <td>manage.roles</td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><input class="form-check-input checkbox-solid-light" id="solid2" type="checkbox"></th>
                                        <td>manage.corporates</td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </dv>
        </div>

        <div class="card-footer text-end">
            <button class="btn btn-primary" type="submit">Update</button>
        </div>
    </form>
</template>

<script>

</script>
