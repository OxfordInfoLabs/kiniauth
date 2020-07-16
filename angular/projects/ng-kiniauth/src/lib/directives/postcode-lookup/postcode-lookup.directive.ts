import { Directive, Input } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';
import { Observable } from 'rxjs/internal/Observable';

@Directive({
    selector: '[netPostcodeLookup]',
    exportAs: 'postcodeLookup'
})
export class PostcodeLookupDirective {

    private searchURL = '/internal/application/searchAddress';

    public results: any[] = [];
    public match = false;
    public complete = false;

    @Input() postcode: Observable<string>;
    @Input() country: Observable<string>;

    constructor(private kbRequest: KinibindRequestService) {
    }

    public search(postcode, country) {
        this.results = [];
        this.complete = false;
        return this.kbRequest.makePostRequest(this.searchURL,
            { term: postcode, countryCode: country }).toPromise()
            .then((results: any) => {
                this.results = results;
                this.complete = true;
            });
    }

}
