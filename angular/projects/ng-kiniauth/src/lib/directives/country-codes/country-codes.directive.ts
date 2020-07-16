import { Directive, Input, OnInit } from '@angular/core';
import { KinibindRequestService } from 'ng-kinibind';

@Directive({
    selector: '[netCountryCodes]',
    exportAs: 'countryCodes'
})
export class CountryCodesDirective implements OnInit {

    private searchURL = '/internal/application/countryCodes';

    public results: any[] = [];

    @Input() promotedCountries: string;
    @Input() valueFormat: string;
    @Input() onInit: boolean;

    constructor(private kbRequest: KinibindRequestService) {
    }

    public search() {
        const params: any = {};

        if (this.promotedCountries) {
            params.promotedCountries = this.promotedCountries;
        }

        if (this.valueFormat) {
            params.valueFormat = this.valueFormat;
        }

        return this.kbRequest.makePostRequest(this.searchURL, params).toPromise()
            .then((results: any) => this.results = results);
    }

    ngOnInit(): void {
        if (this.onInit) {
            this.search();
        }
    }

}
