<template>
    <div class="tab-pane fade" id="pills-app" role="tabpanel" aria-labelledby="pills-app-tab">
        <div class="card mb-0">
            <div class="card-header d-flex">
                <h5 class="mb-0">App Settings</h5>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label>App Name</label>
                                <input class="form-control" type="text" placeholder="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label>App Logo</label>
                                <input class="form-control" type="file" placeholder="">
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
import DropZone from 'dropzone-vue';
export default {
    components: {
        DropZone
    },
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

<style scoped>
.dropzone__item-thumbnail  {
    width: 100%;
    height: 100%;
}

.dropzone__item-thumbnail > img {
    width: 100%;
}
</style>
