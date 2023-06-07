<template>
    <form class="card">
        <div class="card-header ">
            <h4 class="card-title mb-0">Detail Role</h4>
            <div class="card-options"><a class="card-options-collapse" href="javascript:void(0)"
                    data-bs-toggle="card-collapse"><i class="fe fe-chevron-up"></i></a><a class="card-options-remove"
                    href="javascript:void(0)" data-bs-toggle="card-remove"><i class="fe fe-x"></i></a></div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6 col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input class="form-control" type="text" placeholder="Name" disabled>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="col">
                        <div class="form-group m-t-15 mb-0">

                          <div class="checkbox checkbox-solid-primary">
                            <input id="solid2" type="checkbox" disabled="">
                            <label for="solid2">manage.users</label>
                          </div>

                          <div class="checkbox checkbox-solid-primary">
                            <input id="solid2" type="checkbox" disabled="">
                            <label for="solid2">manage.roles</label>
                          </div>

                          <div class="checkbox checkbox-solid-primary">
                            <input id="solid2" type="checkbox" disabled="">
                            <label for="solid2">manage.corporates</label>
                          </div>

                        </div>
                      </div>
                    </div>
                </div>

            </div>
        </div>

    </form>
</template>

<script>

</script>
