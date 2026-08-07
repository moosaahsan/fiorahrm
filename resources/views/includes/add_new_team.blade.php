<div class="modal fade " id="add_new_team">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><b>Add Team</b></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="card-body text-left">
                    <form method="POST" id="add-employees" action="{{ route('store.team') }}">
                        @csrf
                        <ul class="error-container">
                        </ul>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="name">Team Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Team Name"
                                        id="name" name="name" required />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="leave_reason">Descrption</label>
                            <textarea class="with-border" placeholder="Submit Reason..." name="descrption" id="descrption" cols="7"
                                required=""></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="reset" class="btn btn-secondary waves-effect m-l-5" data-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary waves-effect waves-light">
                                Submit
                            </button>
                        </div>
                    </form>

                </div>
            </div>


        </div>

    </div>
</div>