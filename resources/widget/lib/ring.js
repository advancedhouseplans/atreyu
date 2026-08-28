export class Ring {
    constructor(size) {
        this.size = size;
        this.items = [];
    }

    push(item) {
        if (this.size <= 0) return;
        this.items.push(item);
        if (this.items.length > this.size) {
            this.items.splice(0, this.items.length - this.size);
        }
    }

    toArray() {
        return this.items.slice();
    }
}
