import { Component, EventEmitter, OnInit, Output, ViewEncapsulation } from '@angular/core';

@Component({
    selector: 'ka-inline-modal',
    templateUrl: './inline-modal.component.html',
    styleUrls: ['./inline-modal.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class InlineModalComponent implements OnInit {

    @Output('closed') closed: EventEmitter<boolean> = new EventEmitter();

    constructor() {
    }

    ngOnInit() {
    }

    public back() {
        this.closed.emit(true);
    }

}
