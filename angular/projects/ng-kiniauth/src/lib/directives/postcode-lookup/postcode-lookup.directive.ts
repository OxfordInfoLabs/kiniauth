import { Directive, Input } from '@angular/core';
import { Observable } from 'rxjs/internal/Observable';
import {HttpClient} from '@angular/common/http';

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

    constructor(private http: HttpClient) {
    }

    public search(postcode, country) {
        this.results = [];
        this.complete = false;
        return this.http.post(this.searchURL,
            { term: postcode, countryCode: country }).toPromise()
            .then((results: any) => {
                this.results = results;
                this.complete = true;
            });
    }

}
