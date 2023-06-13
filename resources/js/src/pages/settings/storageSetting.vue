<template>
    <div class="tab-pane fade" id="pills-storage" role="tabpanel" aria-labelledby="pills-storage-tab">
        <div class="card mb-0">
            <div class="card-header d-flex">
                <h5 class="mb-0">Storage Settings</h5>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Access Key</label>
                                <input class="form-control" type="password" placeholder="">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Secret Key</label>
                                <input class="form-control" type="password" placeholder="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Full URL</label>
                                <input class="form-control" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>API Endpoint</label>
                                <input class="form-control" type="text" placeholder="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Bucket Name</label>
                                <input class="form-control" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Region</label>
                                <input class="form-control" type="text" placeholder="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex">
                <div class="row">
                    <div class="col">
                        <div class="text-end">
                            <a class="btn btn-success me-3" href="javascript:void(0)">Save</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

export default {
    methods: {
        basic_warning_alert: function (item) {
            this.$swal({
                icon: 'warning',
                title: "Are you sure?",
                text: 'Once deleted, you will not be able to recover this imaginary file!',
                showCancelButton: true,
                confirmButtonText: 'Ok',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result) => {
                if (result.value) {
                    this.tags.splice(item, 1)
                    this.$swal({
                        icon: 'success',
                        text: 'Poof! Your imaginary file has been deleted!',
                        type: 'success',

                    });
                } else {
                    this.$swal({
                        text: 'Your imaginary file is safe!'
                    });
                }
            });
        }
    }
}
</script>
